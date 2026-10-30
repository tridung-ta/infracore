<?php
/** Validation and fail-closed native schema contract for new accounts only. */
class ItaUserCreationInput
{
    public static function validate(array $input,ItaUserObjectInfo $actor): array
    {
        $username=ItaInput::text($input['username'] ?? '',50);
        $name=ItaInput::text($input['fullname'] ?? '',100);
        $email=ItaInput::text($input['email'] ?? '',100);
        $password=$input['password'] ?? null;
        if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]{2,49}\z/D',$username)) {
            throw new ItaHttpError(422,'Tên đăng nhập không hợp lệ: cần 3–50 ký tự không dấu, bắt đầu bằng chữ hoặc số; chỉ thêm dấu chấm, gạch ngang, gạch dưới, không có khoảng trắng.');
        }
        if ($name==='' || preg_match('/\A[\s\p{Z}\p{Cf}]*\z/u',$name) || preg_match('/\p{Cc}/u',$name)) {
            throw new ItaHttpError(422,'Họ tên bị trống hoặc chứa ký tự điều khiển. Hãy nhập họ tên có nội dung, không chỉ khoảng trắng hay ký tự vô hình.');
        }
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { throw new ItaHttpError(422,'Email không đúng định dạng. Hãy nhập địa chỉ dạng ten@example.com.'); }
        if (!is_string($password) || !mb_check_encoding($password,'UTF-8') || mb_strlen($password,'UTF-8')<6 || strlen($password)>72 || str_contains($password,"\0") || preg_match('/\A[\s\p{Z}\p{Cf}]*\z/u',$password)
            || !is_string($input['password_confirmation'] ?? null) || !hash_equals($password,$input['password_confirmation'])) {
            throw new ItaHttpError(422,'Mật khẩu không hợp lệ: cần ít nhất 6 ký tự và tối đa 72 byte, không chứa ký tự NUL hoặc chỉ gồm khoảng trắng/ký tự vô hình. Nhập lại mật khẩu phải giống hệt.');
        }
        $permissions=ItaPermissions::validate($input['permissions'] ?? null);
        ItaPermissions::dependencies($permissions);
        if (!$permissions) { throw new ItaHttpError(422,'Hãy chọn ít nhất một quyền sử dụng.'); }
        if (array_diff($permissions,$actor->permissions)) { throw new ItaHttpError(403,'Không được cấp quyền ngoài phạm vi được ủy quyền.'); }
        $raw=$input['department_ids'] ?? null;
        if (!is_array($raw) || count($raw)>500) { throw new ItaHttpError(422,'Danh sách bộ phận không hợp lệ.'); }
        $scopes=array_values(array_unique(array_map([ItaInput::class,'positiveInt'],$raw)));sort($scopes);
        if (!in_array('scope.store',$permissions,true) && !$scopes) { throw new ItaHttpError(422,'Hãy chọn bộ phận phụ trách hoặc quyền phạm vi toàn hệ thống.'); }
        return ['username'=>$username,'fullname'=>$name,'email'=>$email,'password'=>$password,'permissions'=>$permissions,'department_ids'=>$scopes];
    }

    public static function nativeFields(array $columns,array $data): array
    {
        $schema=array_column($columns,null,'Field');
        foreach (['id','store_id','username','fullname','email','password','password_hash','type','status'] as $required) {
            if (!isset($schema[$required])) { throw new ItaHttpError(503,'Cấu trúc tài khoản nền chưa hỗ trợ tạo mới.'); }
        }
        if (!str_contains(strtolower($schema['id']['Extra'] ?? ''),'auto_increment')) { throw new ItaHttpError(503,'ID native chưa hỗ trợ tạo tự động.'); }
        $fields=['store_id'=>$data['store_id'],'username'=>$data['username'],'fullname'=>$data['fullname'],'email'=>$data['email'],
            'password'=>bin2hex(random_bytes(16)),'password_hash'=>password_hash($data['password'],PASSWORD_DEFAULT),'type'=>'1','status'=>0];
        $defaults=['code'=>'','qrcode'=>'','active_2fa'=>0,'address'=>'','tel'=>'','avatar'=>'','cell'=>'','properties'=>serialize([]),'date_created'=>gmdate('Y-m-d H:i:s')];
        foreach ($schema as $key=>$column) {
            if ($key==='id' || array_key_exists($key,$fields) || ($column['Null'] ?? '')==='YES'
                || ($column['Default'] ?? null)!==null || str_contains(strtolower($column['Extra'] ?? ''),'generated')) { continue; }
            if (!array_key_exists($key,$defaults)) { throw new ItaHttpError(503,'Cột native bắt buộc chưa có quy tắc tạo mới; cần đối chiếu cấu trúc trước.'); }
            $fields[$key]=$defaults[$key];
        }
        foreach ($fields as $key=>$value) {
            if (preg_match('/^(?:var)?char\((\d+)\)/i',$schema[$key]['Type'] ?? '',$match) && mb_strlen((string)$value,'UTF-8')>(int)$match[1]) {
                $label=['username'=>'Tên đăng nhập','fullname'=>'Họ tên','email'=>'Email'][$key] ?? 'Thông tin tài khoản';
                throw new ItaHttpError(422,$label.' vượt giới hạn '.$match[1].' ký tự của hệ thống hiện tại. Hãy rút ngắn trường này rồi lưu lại.');
            }
        }
        return $fields;
    }
}
