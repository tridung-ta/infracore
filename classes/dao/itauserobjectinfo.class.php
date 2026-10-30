<?php
require_once __DIR__.'/../security/itapermissions.class.php';
class ItaUserObjectInfo
{
    public readonly int $id;
    public readonly int $storeId;
    public readonly string $role;
    public readonly int $revision;
    public readonly string $name;
    public readonly bool $capabilitiesEnabled;
    public readonly array $permissions;

    public function __construct(array $identity, public readonly array $departmentIds = [])
    {
        $this->id = (int) $identity['id'];
        $this->storeId = (int) $identity['store_id'];
        $this->role = $identity['role'];
        $this->revision = (int) $identity['auth_revision'];
        $this->name = $identity['fullname'] ?: $identity['username'];
        $this->capabilitiesEnabled = ItaPermissions::enabled();
        $this->permissions = $this->capabilitiesEnabled ? ItaPermissions::validate($identity['permissions'] ?? []) : ItaPermissions::preset($this->role);
        if ($this->capabilitiesEnabled) { ItaPermissions::dependencies($this->permissions); }
    }

    public function isAdmin(): bool
    {
        return !$this->capabilitiesEnabled && $this->role === 'admin_it';
    }

    public function roleLabel(): string
    {
        if ($this->capabilitiesEnabled) { return 'Quyền theo chức năng'; }
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
        if (!$this->hasGlobalScope() && !in_array($departmentId, $this->departmentIds, true)) {
            throw new ItaHttpError(403, 'Thiết bị nằm ngoài bộ phận bạn phụ trách.');
        }
    }

    public function can(string $permission): bool
    {
        return in_array($permission,$this->permissions,true);
    }

    public function requireCapability(string $permission): void
    {
        if (!$this->can($permission)) { throw new ItaHttpError(403,'Bạn chưa được cấp quyền cho chức năng này.'); }
    }

    public function hasGlobalScope(): bool
    {
        return $this->can('scope.store');
    }
}
