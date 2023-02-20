<?php
namespace App\Models;

trait PermissionTrait{

    public function userRole($item_id){
        $session = session();
        /*
        if( $this->isAdmin() ){
            return 'admin';
        }
        */
        $user_id = $session->get('user_id') ?? 0;
        if( $user_id == 0 ){
            return 'other';//unsigned user (guest)
        }
        /*
        if( $user_id > 0 && !$item_id ){
            return 'owner';//new item
        }*/
        
        if(count($this->shared_selector) == 0 && $this->query("SHOW TABLES LIKE '".$this->table."_usermap'")->getNumRows() > 0){
            $this->shared_selector[] = " (SELECT user_id FROM ".$this->table."_usermap WHERE item_id = '$item_id' AND user_id = $user_id) ";
        }
        $sql="
            SELECT
                IF(owner_id = $user_id
                    ,'owner',
                IF(COALESCE(".implode(' AND ', $this->shared_selector).", 0)
                    ,'shared'
                    ,'other'
                )) user_role
            FROM
                $this->table 
            WHERE
                $this->primaryKey='$item_id'
            ";
        return $this->query($sql)->getRow('user_role');
    }
    public function getStatus($item_id){
        return $this->where("$this->primaryKey='$item_id' AND is_private = 1")->get()->getRow('is_private') ? 'private' : 'public';
    }
    
    public function hasPermission( $item_id, $right, $method = 'item' ){
        $scope = (new \ReflectionClass($this))->getShortName();
        
        $status = $this->getStatus($item_id);
        $permission_name = "permit.{$scope}.{$method}.{$status}.{$item_id}.{$right}";
        /*
        $cached_permission = session()->get($permission_name);
        if( isset($cached_permission) ){
            return $cached_permission;
        }*/
        $user_role = $this->userRole($item_id);

        $permissions = session()->get('permissions');
        $permission = 0;
        if($user_role == 'admin'){
            $permission = 1;//grant all permissions to admin
        } else
        if( isset($permissions["$scope.$method"][$status][$user_role]) ){
            $rights = $permissions["$scope.$method"][$status][$user_role];
            $permission = str_contains($rights, $right) ? 1 : 0;
        }
        //session()->set($permission_name, $permission);
        return $permission;
    }
    public function whereHasPermission( $right, $method = 'item'){
        $permission_filter = $this->getPermissionFilter($right, $method);
        if($permission_filter != ""){
            $this->where('('.$permission_filter.')');
        }
        return $this;
    }
    public function getPermissionFilter($right, $method){
        /*
        if( $this->isAdmin() ){
            return "1=1";//All granted
        }
        */
        $user_id = session()->get('user_id');
        $scope = (new \ReflectionClass($this))->getShortName();
        $permission_name="permitWhere.{$scope}.{$method}.{$user_id}.{$right}";
        /*
        $cached_permission=session()->get($permission_name);
        if( isset($cached_permission) ){
            return $cached_permission;
        }*/
        $permissions = session()->get('permissions');

        $permission_filter = "1=2"; //All denied
        if( isset($permissions["$scope.$method"]) ){
            $scopePermissions = $permissions["$scope.$method"];
            $permission_filter = $this->composeFilterQuery($scopePermissions, $right);
        }
        //session()->set($permission_name, $permission_filter);
        return $permission_filter;
    }
    
    private function composeFilterQuery($permissions, $right){
        $result = [];
        $user_id = session()->get('user_id');
        foreach($permissions as $status => $permission){
            $owner_has  = str_contains($permission['owner'], $right);
            $shared_has = str_contains($permission['shared'], $right);
            $other_has  = str_contains($permission['other'],$right);

            if( $owner_has && $shared_has && $other_has ){
                $query = "";//All granted
            } else
            if( !$owner_has && !$shared_has && !$other_has ){
                $query = "1=2";//All denied
            } else
            if( $owner_has && $shared_has ){//!$other_has
                $query = "({$this->table}.owner_id='$user_id' OR ".implode(' AND ', $this->shared_selector)." )";
            } else
            if( $owner_has && $other_has ){//!$shared_has
                $query = "NOT FIND_IN_SET('$user_id',{$this->table}.owner_shared_ids)";
            } else
            if( $shared_has ){
                $query = "(".implode(' AND ', $this->shared_selector).") IS NOT NULL";
            } else
            if( $shared_has && $other_has ){//!$owner_has
                $query = "{$this->table}.owner_id<>'$user_id'";
            } else
            if( $owner_has ){
                $query = "{$this->table}.owner_id='$user_id'";
            } else
            if( $other_has ){
                $query = "{$this->table}.owner_id<>'$user_id' AND (".implode(' AND ', $this->shared_selector).") IS NULL)";
            }
            if($query == ""){
                $query = " {$this->table}.is_private = '".(int) ($status == 'private')."'";
            } else {
                $query .= " AND {$this->table}.is_private = '".(int) ($status == 'private')."'";
                
            }
            foreach($this->shared_queue as $table => $field_name){
                //$query .= " AND (SELECT is_private FROM $table WHERE $table.id = $this->table.$field_name) = '".(int) ($status == 'private')."' ";
            }
            $result[] = '('.$query.')';
        }

        print_r('('.implode(' OR ', $result).')');
        die;
        if(count($result) > 0){
            return '('.implode(' OR ', $result).')';
        }
        return '';
        
    }
    private $shared_selector = [];
    private $shared_queue = [];
    public function useSharedOf( $table, $field_name ){
        if($this->query("SHOW TABLES LIKE '".$table."_usermap'")->getNumRows() > 0){
            $subscription_query = " (SELECT `".$table."_usermap`.user_id FROM ".$table."_usermap WHERE `".$table."_usermap`.item_id = $this->table.$field_name AND `".$table."_usermap`.user_id = ".session()->get('user_id').")";
            $this->shared_selector[] = $subscription_query;
            $this->shared_queue[$table] = $field_name;
        }
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