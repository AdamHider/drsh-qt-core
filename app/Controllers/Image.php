<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Files\File;
class Image extends BaseController
{
    use ResponseTrait;
    public function uploadItem()
    {
        $validationRule = [
            'image' => [
                'label' => 'Image File',
                'rules' => [
                    'uploaded[image]',
                    'is_image[image]',
                    'mime_in[image,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[image,2000]',
                    'max_dims[image,1500,1500]',
                ],
            ],
        ];
        
        if (! $this->validate($validationRule)) {
            $data = ['errors' => $this->validator->getErrors()];
            return $this->fail($data);
        }

        $img = $this->request->getFile('image');
        

        if (!$img->hasMoved()) {
            $filename = $img->getRandomName();
            $img->move(ROOTPATH . 'public/image', $filename);
            return $this->respond(['image' => base_url('image/' . $filename)]);
        }

        $data = ['errors' => 'The file has already been moved.'];

        return $this->fail($data);
    }
    
    private function fileSaveImage( $image_holder_id, $file, $image_holder='store' ){
        $image_holder=($image_holder=='store_avatar'?'store_avatar':'store');
        $image_data=[
            'image_holder'=>$image_holder,
            'image_holder_id'=>$image_holder_id
        ];
        $StoreModel=model('StoreModel');
        $image_hash=$StoreModel->imageCreate($image_data);
        if( !$image_hash ){
            return $this->failForbidden('forbidden');
        }
        if( $image_hash === 'limit_exeeded' ){
            return $this->fail('limit_exeeded');
        }
        $file->move(WRITEPATH.'images/', $image_hash.'.webp');
        
        try{
            return \Config\Services::image()
            ->withFile(WRITEPATH.'images/'.$image_hash.'.webp')
            ->resize(1600, 1600, true, 'height')
            ->convert(IMAGETYPE_WEBP)
            ->save();
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

}
