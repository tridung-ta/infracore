-- Generated from ItaLifecycleObjects::queryPlans. Read-only EXPLAIN, no execution by generator.
-- Example date 2026-10-21, store1; manager example scope department1. Change examples to your actual scope/date.
-- Run in the intended schema in phpMyAdmin. EXPLAIN plans are still UNVERIFIED.

-- admin_it / count
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT COUNT(*) n FROM graded;

-- admin_it / list
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT * FROM graded ORDER BY score_raw IS NULL,score_raw DESC,id ASC LIMIT 20 OFFSET 0;

-- admin_it / summary
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT COUNT(*) total,SUM(CASE WHEN repair_count>0 THEN 1 ELSE 0 END) failed_assets,COUNT(age_years) age_known,AVG(age_years) average_age_years,SUM(CASE WHEN tier='missing' THEN 1 ELSE 0 END) missing_scores FROM graded;

-- admin_it / category
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT category_id group_id,category_name group_name,COUNT(*) total,SUM(CASE WHEN repair_count>0 THEN 1 ELSE 0 END) failed_assets,COUNT(age_years) age_known,AVG(age_years) average_age_years,SUM(CASE WHEN tier='missing' THEN 1 ELSE 0 END) missing_scores FROM graded GROUP BY category_id,category_name ORDER BY category_name,category_id;

-- admin_it / department
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT department_id group_id,department_name group_name,COUNT(*) total,SUM(CASE WHEN repair_count>0 THEN 1 ELSE 0 END) failed_assets,COUNT(age_years) age_known,AVG(age_years) average_age_years,SUM(CASE WHEN tier='missing' THEN 1 ELSE 0 END) missing_scores FROM graded GROUP BY department_id,department_name ORDER BY department_name,department_id;

-- system_manager / count
EXPLAIN WITH cfg AS (SELECT CAST('2026-10-21' AS DATE) as_of,CAST('0.5' AS DECIMAL(12,6)) w_age,CAST('0.3' AS DECIMAL(12,6)) w_repairs,CAST('0.2' AS DECIMAL(12,6)) w_cost,CAST('0.8' AS DECIMAL(12,6)) replace_threshold,CAST('0.6' AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0 AND a.department_id IN (1)),
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT COUNT(*) n FROM graded;

-- system_manager / list
EXPLAIN WITH cfg AS (SELECT CAST('2026-10-21' AS DATE) as_of,CAST('0.5' AS DECIMAL(12,6)) w_age,CAST('0.3' AS DECIMAL(12,6)) w_repairs,CAST('0.2' AS DECIMAL(12,6)) w_cost,CAST('0.8' AS DECIMAL(12,6)) replace_threshold,CAST('0.6' AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0 AND a.department_id IN (1)),
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT * FROM graded ORDER BY score_raw IS NULL,score_raw DESC,id ASC LIMIT 20 OFFSET 0;

-- system_manager / summary
EXPLAIN WITH cfg AS (SELECT CAST('2026-10-21' AS DATE) as_of,CAST('0.5' AS DECIMAL(12,6)) w_age,CAST('0.3' AS DECIMAL(12,6)) w_repairs,CAST('0.2' AS DECIMAL(12,6)) w_cost,CAST('0.8' AS DECIMAL(12,6)) replace_threshold,CAST('0.6' AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0 AND a.department_id IN (1)),
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT COUNT(*) total,SUM(CASE WHEN repair_count>0 THEN 1 ELSE 0 END) failed_assets,COUNT(age_years) age_known,AVG(age_years) average_age_years,SUM(CASE WHEN tier='missing' THEN 1 ELSE 0 END) missing_scores FROM graded;

-- system_manager / category
EXPLAIN WITH cfg AS (SELECT CAST('2026-10-21' AS DATE) as_of,CAST('0.5' AS DECIMAL(12,6)) w_age,CAST('0.3' AS DECIMAL(12,6)) w_repairs,CAST('0.2' AS DECIMAL(12,6)) w_cost,CAST('0.8' AS DECIMAL(12,6)) replace_threshold,CAST('0.6' AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0 AND a.department_id IN (1)),
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT category_id group_id,category_name group_name,COUNT(*) total,SUM(CASE WHEN repair_count>0 THEN 1 ELSE 0 END) failed_assets,COUNT(age_years) age_known,AVG(age_years) average_age_years,SUM(CASE WHEN tier='missing' THEN 1 ELSE 0 END) missing_scores FROM graded GROUP BY category_id,category_name ORDER BY category_name,category_id;

-- system_manager / department
EXPLAIN WITH cfg AS (SELECT CAST('2026-10-21' AS DATE) as_of,CAST('0.5' AS DECIMAL(12,6)) w_age,CAST('0.3' AS DECIMAL(12,6)) w_repairs,CAST('0.2' AS DECIMAL(12,6)) w_cost,CAST('0.8' AS DECIMAL(12,6)) replace_threshold,CAST('0.6' AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE a.store_id=1 AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0 AND a.department_id IN (1)),
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
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) SELECT department_id group_id,department_name group_name,COUNT(*) total,SUM(CASE WHEN repair_count>0 THEN 1 ELSE 0 END) failed_assets,COUNT(age_years) age_known,AVG(age_years) average_age_years,SUM(CASE WHEN tier='missing' THEN 1 ELSE 0 END) missing_scores FROM graded GROUP BY department_id,department_name ORDER BY department_name,department_id;
