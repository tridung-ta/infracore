<?php
class ItaUserObjectInfo
{
    public readonly int $id;
    public readonly int $storeId;
    public readonly string $role;
    public readonly int $revision;
    public readonly string $name;

    public function __construct(array $identity, public readonly array $departmentIds = [])
    {
        $this->id = (int) $identity['id'];
        $this->storeId = (int) $identity['store_id'];
        $this->role = $identity['role'];
        $this->revision = (int) $identity['auth_revision'];
        $this->name = $identity['fullname'] ?: $identity['username'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin_it';
    }

    public function roleLabel(): string
    {
        return ['admin_it'=>'Admin IT','system_manager'=>'Quản lý hệ thống','support_technician'=>'Hỗ trợ kỹ thuật'][$this->role] ?? '';
    }

    public function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            throw new ItaHttpError(403, 'Bạn không có quyền truy cập chức năng này.');
        }
    }

    public function requireDepartment(?int $departmentId): void
    {
        if (!$this->isAdmin() && !in_array($departmentId, $this->departmentIds, true)) {
            throw new ItaHttpError(403, 'Thiết bị nằm ngoài bộ phận bạn phụ trách.');
        }
    }
}
