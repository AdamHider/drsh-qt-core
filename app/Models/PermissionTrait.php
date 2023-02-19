<?php
namespace App\Models;

trait PermissionTrait{

    public function userRole($item_id){
        $session = session();
        if( $this->isAdmin() ){
            return 'admin';
        }
        $user_id = $session->get('user_id')??-1;
        if( $user_id == 0 ){
            return 'other';//unsigned user (guest)
        }
        if( $user_id > 0 && !$item_id ){
            return 'owner';//new item
        }
         $sql="
            SELECT
                IF(owner_id = $user_id
                    ,'owner',
                IF(COALESCE($this->party_selector, 0)
                    ,'party'
                    ,'other'
                )) user_role
            FROM
                $this->table 
            WHERE
                $this->primaryKey='$item_id'
            ";
        return $this->query($sql)->getRow('user_role');
    }
    public function isPrivate($item_id){
        return $this->where("$this->primaryKey='$item_id' AND is_private = 1")->get()->getRow('is_private');
    }
    
    public function hasPermission( $item_id, $right, $method = 'item' ){
        $class_name = (new \ReflectionClass($this))->getShortName();
        
        $is_private = 'public';
        if($this->isPrivate($item_id)){
            $is_private = 'private';
        }

        $permission_name = "permit.{$class_name}.{$is_private}.{$item_id}.{$right}";
        /*
        $cached_permission = session()->get($permission_name);
        if( isset($cached_permission) ){
            return $cached_permission;
        }*/
        $PermissionModel = model('PermissionModel');
        $max_user_group = array_reverse(session()->get('user_data')['group_ids'])[0];
        $user_role = $this->userRole($item_id);
        //$permissions = session()->get('permissions');
        $permission = 0;
        if($user_role == 'admin'){
            $permission = 1;//grant all permissions to admin
        } else
        //if( isset($permissions["$class_name.$method"][$user_role]) ){
            $rights = $PermissionModel->where("scope = '$class_name' AND method = '$is_private'")->where('user_group_id', $max_user_group)->select($user_role)->get()->getRow($user_role);
            
            /*
            $rights = $permissions["$class_name.$method"][$user_role];
            */
            $permission = str_contains($rights, $right) ? 1 : 0;
        //}
        session()->set($permission_name, $permission);
        return $permission;
    }
    private $party_selector = "0";
    public function considerSubscription( $table, $field_name ){
        if($this->query("SHOW TABLES LIKE 'users_to_".$table."'")->getNumRows() > 0){
            $subscription_query = " (SELECT user_id FROM users_to_$table WHERE item_id = $this->table.$field_name AND user_id = ".session()->get('user_id').") ";
            if($this->party_selector == '0'){
                $this->party_selector = $subscription_query;
            } else {
                $this->party_selector .= " AND ".$subscription_query;
            }
        }
    }
    
    public function permitWhere( $right, $method = 'public' ){
        $permission_filter = $this->permitWhereGet($right,$method);
        if($permission_filter!=""){
            //echo $permission_filter;
            $this->where($permission_filter);
        }
        return $this;
    }
    
    public function permitWhereGet( $right, $method ){
        if( $this->isAdmin() ){
            return "1=1";//All granted
        }
        $user_id=session()->get('user_id');
        $permited_class_name=(new \ReflectionClass($this))->getShortName();
        $permission_name="permitWhere.{$permited_class_name}.{$method}.{$user_id}.{$right}";
        /*
        $cached_permission=session()->get($permission_name);
        if( isset($cached_permission) ){
            return $cached_permission;
        }*/
        $PermissionModel = model('PermissionModel');
        $max_user_group = array_reverse(session()->get('user_data')['group_ids'])[0];
        //$permissions=session()->get('permissions');
        $permission_filter = "1=2";//All denied
        //if( isset($permissions["{$permited_class_name}.{$method}"]) ){
            $modelPerm = $PermissionModel->where("scope = '$permited_class_name' AND method = '$method'")->where('user_group_id', $max_user_group)->select('owner, party, other')->get()->getResultArray();
            
            $permission_filter = $this->permitWhereCompose($user_id, $modelPerm, $right);
        //}
        //session()->set($permission_name,$permission_filter);
        return $permission_filter;
    }
    
    private function permitWhereCompose($user_id, $modelPerm, $right){

//        if( $user_id>0 ){
            $owner_has = str_contains($modelPerm['owner'],$right);
            $party_has = str_contains($modelPerm['party'],$right);
//        } else {
//            $owner_has=false;
//            $party_has=false;
//        }
        $other_has = str_contains($modelPerm['other'],$right);
        //echo "owner_has $owner_has party_has $party_has other_has $other_has";
        if( $owner_has && $party_has && $other_has ){
            $permission_filter = "";//All granted
        } else
        if( !$owner_has && !$party_has && !$other_has ){
            $permission_filter = "1=2";//All denied
        } else
        if( $owner_has && $party_has ){//!$other_has
            $permission_filter = "({$this->table}.owner_id='$user_id' OR ($this->party_selector) IS NOT NULL)";
        } else
        if( $owner_has && $other_has ){//!$party_has
            $permission_filter = "NOT FIND_IN_SET('$user_id',{$this->table}.owner_party_ids)";
        } else
        if( $party_has ){
            $permission_filter = "($this->party_selector) IS NOT NULL";
        } else
        if( $party_has && $other_has ){//!$owner_has
            $permission_filter = "{$this->table}.owner_id<>'$user_id'";
        } else
        if( $owner_has ){
            $permission_filter = "{$this->table}.owner_id='$user_id'";
        } else
        if( $other_has ){
            $permission_filter = "{$this->table}.owner_id<>'$user_id' AND ($this->party_selector) IS NULL)";
        }
        return $permission_filter;
    }

    public function isAdmin(){
        foreach(session()->get('user_data')['groups'] as $group){
            if($group['code'] == 'admin'){
               return true;
            }
        }
        return false;
    }
}