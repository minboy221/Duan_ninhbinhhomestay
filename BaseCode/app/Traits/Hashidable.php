<?php
namespace App\Traits;

use Vinkla\Hashids\Facades\Hashids;

trait Hashidable{
    //tự động chuyển đổi ID thành chuỗi mã hoá Hashids khi sinh URL
    public function getRouteKey(){
        return Hashids::encode($this->getKey());
    }
    //tự động giải mã Hashids từ URL ngược lại thành ID số để truy vẫn database
    public function resolveRouteBinding($value,$field = null){
        $decoded = Hashids::decode($value);
        if(!empty($decoded)){
            $item = $this->where($field ?? $this->getKeyName(), $decoded[0])->first();
            if ($item) {
                return $item;
            }
        }
        // Truy vấn dự phòng nếu truyền trực tiếp ID dạng số nguyên
        return $this->where($field ?? $this->getKeyName(), $value)->first();
    }
    //tự động đính kèm trường 'hash_id' khi chuyền model thành dữ liệu json cho vue
    public function getHashIdAttribute(){
        return Hashids::encode($this->id);
    }
}
?>