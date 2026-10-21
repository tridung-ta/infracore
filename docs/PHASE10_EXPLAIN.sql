-- Exact source read plans, synthetic store 1 / manager scope 10. Generator does NOT execute SQL.
-- User-run EXPLAIN on the intended DB only; indexes/performance UNVERIFIED. No DDL.
-- Group counts are computed in PHP from bounded scoped rows (maximum 5000, overflow refused).

-- admin_it / assets
EXPLAIN SELECT a.id,a.asset_code,a.name,a.category_id,a.department_id,a.status,a.purchase_date,a.warranty_end,c.name category_name,d.name department_name,COALESCE(a.next_maintenance_due,DATE(DATE_ADD(COALESCE(CONVERT_TZ(a.last_maintenance_at,'+00:00','+07:00'),a.purchase_date),INTERVAL COALESCE(c.maintenance_cycle_months,6) MONTH))) maintenance_due,
            (a.status='maintenance' AND a.current_user_id IS NULL AND NOT EXISTS (SELECT 1 FROM ita_assignments x WHERE x.store_id=a.store_id AND x.asset_id=a.id AND x.returned_at IS NULL) AND NOT EXISTS (SELECT 1 FROM ita_tickets p WHERE p.store_id=a.store_id AND p.asset_id=a.id AND p.status='pending') AND (SELECT t.status FROM ita_tickets t WHERE t.store_id=a.store_id AND t.asset_id=a.id ORDER BY COALESCE((SELECT MAX(e.created_at) FROM ita_ticket_events e WHERE e.store_id=t.store_id AND e.ticket_id=t.id),t.opened_at) DESC,(SELECT MAX(e.id) FROM ita_ticket_events e WHERE e.store_id=t.store_id AND e.ticket_id=t.id) DESC,t.id DESC LIMIT 1)='resolved') awaiting_ready
            FROM ita_assets a LEFT JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 ORDER BY a.id LIMIT 5001;

-- admin_it / licenses
EXPLAIN SELECT l.id,l.software_name,l.status,l.expire_date,l.total_seats,(SELECT COUNT(*) FROM ita_license_allocations x WHERE x.store_id=l.store_id AND x.license_id=l.id AND x.released_at IS NULL) used_seats FROM ita_licenses l WHERE l.store_id=1 ORDER BY l.id LIMIT 5001;

-- admin_it / lifecycle
EXPLAIN WITH cfg AS (SELECT CAST('2026-10-21' AS DATE) as_of,CAST('0.5' AS DECIMAL(12,6)) w_age,CAST('0.3' AS DECIMAL(12,6)) w_repairs,CAST('0.2' AS DECIMAL(12,6)) w_cost,CAST('0.8' AS DECIMAL(12,6)) replace_threshold,CAST('0.6' AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0),
        repair AS (SELECT t.store_id,t.asset_id,COUNT(*) repair_count,SUM(t.cost) repair_cost FROM ita_tickets t JOIN eligible e ON e.store_id=t.store_id AND e.id=t.asset_id
        WHERE t.store_id=1 AND t.type='repair' AND t.opened_at>='2025-10-20 17:00:00' AND t.opened_at<'2026-10-21 17:00:00' GROUP BY t.store_id,t.asset_id),
        base AS (SELECT e.*,cfg.*,DATEDIFF(cfg.as_of,e.purchase_date) age_days,COALESCE(r.repair_count,0) repair_count,COALESCE(r.repair_cost,0) repair_cost
        FROM eligible e CROSS JOIN cfg LEFT JOIN repair r ON r.store_id=e.store_id AND r.asset_id=e.id),
        ratios AS (SELECT base.*,CASE WHEN age_days>=0 THEN CAST(age_days AS DECIMAL(40,24))/365.25 ELSE NULL END age_years,
        CASE WHEN age_days>=0 AND lifespan_years>0 THEN CAST(age_days AS DECIMAL(40,24))/365.25/lifespan_years ELSE NULL END age_ratio,
        LEAST(CAST(repair_count AS DECIMAL(40,24))/3,1) repairs_ratio,
        CASE WHEN status='broken' THEN 1 WHEN purchase_price>0 THEN LEAST(CAST(repair_cost AS DECIMAL(60,24))/purchase_price,1) ELSE NULL END cost_ratio FROM base),
        scored AS (SELECT ratios.*,w_age*age_ratio age_part,w_repairs*repairs_ratio repairs_part,w_cost*cost_ratio cost_part,
        (w_age*age_ratio+w_repairs*repairs_ratio+w_cost*cost_ratio) score_raw FROM ratios),
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT * FROM graded ORDER BY score_raw IS NULL,score_raw DESC,id ASC LIMIT 5001;

-- system_manager / assets
EXPLAIN SELECT a.id,a.asset_code,a.name,a.category_id,a.department_id,a.status,a.purchase_date,a.warranty_end,c.name category_name,d.name department_name,COALESCE(a.next_maintenance_due,DATE(DATE_ADD(COALESCE(CONVERT_TZ(a.last_maintenance_at,'+00:00','+07:00'),a.purchase_date),INTERVAL COALESCE(c.maintenance_cycle_months,6) MONTH))) maintenance_due,
            (a.status='maintenance' AND a.current_user_id IS NULL AND NOT EXISTS (SELECT 1 FROM ita_assignments x WHERE x.store_id=a.store_id AND x.asset_id=a.id AND x.returned_at IS NULL) AND NOT EXISTS (SELECT 1 FROM ita_tickets p WHERE p.store_id=a.store_id AND p.asset_id=a.id AND p.status='pending') AND (SELECT t.status FROM ita_tickets t WHERE t.store_id=a.store_id AND t.asset_id=a.id ORDER BY COALESCE((SELECT MAX(e.created_at) FROM ita_ticket_events e WHERE e.store_id=t.store_id AND e.ticket_id=t.id),t.opened_at) DESC,(SELECT MAX(e.id) FROM ita_ticket_events e WHERE e.store_id=t.store_id AND e.ticket_id=t.id) DESC,t.id DESC LIMIT 1)='resolved') awaiting_ready
            FROM ita_assets a LEFT JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.department_id IN (10) ORDER BY a.id LIMIT 5001;

-- system_manager / licenses
EXPLAIN SELECT l.id,l.software_name,l.status,l.expire_date,l.total_seats,(SELECT COUNT(*) FROM ita_license_allocations x WHERE x.store_id=l.store_id AND x.license_id=l.id AND x.released_at IS NULL) used_seats FROM ita_licenses l WHERE l.store_id=1 AND EXISTS (SELECT 1 FROM ita_license_allocations x LEFT JOIN ita_assets a ON a.store_id=x.store_id AND a.id=x.asset_id AND a.is_deleted=0 WHERE x.store_id=l.store_id AND x.license_id=l.id AND x.released_at IS NULL AND (x.department_id IN (10) OR a.department_id IN (10))) ORDER BY l.id LIMIT 5001;

-- system_manager / lifecycle
EXPLAIN WITH cfg AS (SELECT CAST('2026-10-21' AS DATE) as_of,CAST('0.5' AS DECIMAL(12,6)) w_age,CAST('0.3' AS DECIMAL(12,6)) w_repairs,CAST('0.2' AS DECIMAL(12,6)) w_cost,CAST('0.8' AS DECIMAL(12,6)) replace_threshold,CAST('0.6' AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0 AND a.department_id IN (10)),
        repair AS (SELECT t.store_id,t.asset_id,COUNT(*) repair_count,SUM(t.cost) repair_cost FROM ita_tickets t JOIN eligible e ON e.store_id=t.store_id AND e.id=t.asset_id
        WHERE t.store_id=1 AND t.type='repair' AND t.opened_at>='2025-10-20 17:00:00' AND t.opened_at<'2026-10-21 17:00:00' GROUP BY t.store_id,t.asset_id),
        base AS (SELECT e.*,cfg.*,DATEDIFF(cfg.as_of,e.purchase_date) age_days,COALESCE(r.repair_count,0) repair_count,COALESCE(r.repair_cost,0) repair_cost
        FROM eligible e CROSS JOIN cfg LEFT JOIN repair r ON r.store_id=e.store_id AND r.asset_id=e.id),
        ratios AS (SELECT base.*,CASE WHEN age_days>=0 THEN CAST(age_days AS DECIMAL(40,24))/365.25 ELSE NULL END age_years,
        CASE WHEN age_days>=0 AND lifespan_years>0 THEN CAST(age_days AS DECIMAL(40,24))/365.25/lifespan_years ELSE NULL END age_ratio,
        LEAST(CAST(repair_count AS DECIMAL(40,24))/3,1) repairs_ratio,
        CASE WHEN status='broken' THEN 1 WHEN purchase_price>0 THEN LEAST(CAST(repair_cost AS DECIMAL(60,24))/purchase_price,1) ELSE NULL END cost_ratio FROM base),
        scored AS (SELECT ratios.*,w_age*age_ratio age_part,w_repairs*repairs_ratio repairs_part,w_cost*cost_ratio cost_part,
        (w_age*age_ratio+w_repairs*repairs_ratio+w_cost*cost_ratio) score_raw FROM ratios),
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT * FROM graded ORDER BY score_raw IS NULL,score_raw DESC,id ASC LIMIT 5001;

-- Budget reuses PHASE09_EXPLAIN.sql; actor/scopes use existing primary-key reads.
