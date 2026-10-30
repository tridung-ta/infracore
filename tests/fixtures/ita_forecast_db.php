<?php
/** Scripted transport double; never creates/connects a DB. */
class ForecastTestDb extends ItaDb
{
    public array $calls=[],$plans=[],$categories,$settings=[];
    public bool $auditFails=false,$rolledBack=false;
    public string $role='admin_it';
    private array $backup=[];
    public function __construct()
    {
        $this->categories=[['id'=>1,'name'=>'Máy tính','default_replacement_cost'=>'20000000.00']];
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        $this->calls[]=[$sql,$types,$params];
        if (str_contains($sql,'ita_user_scopes')) { return [['department_id'=>10]]; }
        if (str_contains($sql,'FROM ita_settings')) { return []; }
        if (str_contains($sql,'FROM ita_asset_categories')) { return $this->categories; }
        if (str_contains($sql,'FROM ita_departments')) { return [['id'=>10,'name'=>'CNTT']]; }
        if (str_contains($sql,'FROM ita_budget_plans')) { return array_values($this->plans); }
        if (str_starts_with($sql,'SELECT t.asset_id')) { return [['asset_id'=>1,'n'=>2,'known'=>2,'cost'=>'9000000.00']]; }
        if (str_contains($sql,'FROM graded')) { return [['id'=>1,'store_id'=>1,'asset_code'=>'MOCK-1','name'=>'Thiết bị giả','department_id'=>10,'category_id'=>1,'purchase_date'=>'2015-01-01','lifespan_years'=>'5','tier'=>'replace','score_raw'=>'1.2']]; }
        throw new RuntimeException('Unexpected scripted query');
    }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        if ($sql==='SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1' && $types==='ii' && $params===[1,1]) { return ['id'=>1]; }
        $this->calls[]=[$sql,$types,$params];
        if (str_contains($sql,'FROM ita_user_roles')) { return ['role'=>$this->role,'enabled'=>1,'auth_revision'=>1]; }
        if (str_contains($sql,'FROM ita_settings')) { return isset($this->settings['forecast_category_parameters'])?['setting_value'=>$this->settings['forecast_category_parameters']]:null; }
        if (str_contains($sql,'FROM ita_departments')) { return ['id'=>10]; }
        if (str_contains($sql,'FROM ita_budget_plans')) { return $this->plans[1] ?? null; }
        if (str_contains($sql,'LAST_INSERT_ID')) { return ['id'=>1]; }
        throw new RuntimeException('Unexpected scripted query');
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        $this->calls[]=[$sql,$types,$params];
        if (str_contains($sql,'ita_audit_logs')) { if ($this->auditFails) { throw new RuntimeException('Synthetic audit failure'); }return 1; }
        if (str_contains($sql,'INSERT INTO ita_budget_plans')) {
            $this->plans[1]=['id'=>1,'store_id'=>$params[0],'year'=>$params[1],'department_id'=>$params[2],
                'planned_headcount_increase'=>$params[3],'note'=>$params[4],'calculation_snapshot_json'=>$params[5],
                'created_by'=>$params[6],'status'=>'draft','finalized_at'=>null,'updated_at'=>'synthetic'];return 1;
        }
        if (str_contains($sql,'UPDATE ita_budget_plans')) {
            $this->plans[1]=array_replace($this->plans[1],['planned_headcount_increase'=>$params[0],'note'=>$params[1],'calculation_snapshot_json'=>$params[2]]);return 1;
        }
        if (str_contains($sql,'UPDATE ita_asset_categories')) { $this->categories[0]['default_replacement_cost']=$params[0];return 1; }
        if (str_contains($sql,'INSERT INTO ita_settings')) { $this->settings['forecast_category_parameters']=$params[1];return 1; }
        throw new RuntimeException('Unexpected scripted write');
    }
    public function beginTransaction(): void { $this->backup=[$this->plans,$this->categories,$this->settings]; }
    public function transactionIsolation(): string { return 'REPEATABLE-READ'; }
    public function commit(): void {}
    public function rollBack(): void { [$this->plans,$this->categories,$this->settings]=$this->backup;$this->rolledBack=true; }
}
