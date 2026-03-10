<?php
/**
 * Plugin Name: Calendario Taller
 * Description: Calendario semanal (L–V) para planificación de técnicos — admin + shortcode frontend + exportar día (PNG).
 * Version: 1.9.33
 * Author: Rocket Solutions
 * Author URI: https://www.rocketsolutions.cl
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ACAL_Calendario_Taller {
    const VERSION   = '1.9.33';
    const OPT_TECHS = 'acal_tecnicos';
    const OPT_FRONT_SLUG = 'acal_front_slug';
    const CPT_TASK  = 'acal_tarea';
    const NONCE_KEY = 'acal_nonce';

    public function __construct() {
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_front_route']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('admin_menu', [$this, 'register_admin_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets_frontend']);
        add_shortcode('calendario_taller', [$this, 'shortcode_calendar']);
        add_action('template_redirect', [$this,'maybe_fullscreen']);
        add_action('template_redirect', [$this,'maybe_front_management']);
        add_action('template_redirect', [$this,'maybe_standalone']);

        // Admin-post actions
        add_action('admin_post_acal_create_task', [$this, 'handle_create_task']);
        add_action('admin_post_acal_update_task', [$this, 'handle_update_task']);
        add_action('admin_post_acal_delete_task', [$this, 'handle_delete_task']);
        add_action('admin_post_acal_add_tech', [$this, 'handle_add_tech']);
        add_action('admin_post_acal_update_tech', [$this, 'handle_update_tech']);
        add_action('admin_post_acal_delete_tech', [$this, 'handle_delete_tech']);
        add_action('admin_post_acal_export_day_png', [$this, 'handle_export_day_png']);
        add_action('admin_post_acal_export_data', [$this, 'handle_export_data']);
        add_action('admin_post_acal_import_data', [$this, 'handle_import_data']);
        add_action('admin_post_acal_purge_tasks', [$this, 'handle_purge_tasks']);
        add_action('admin_post_acal_save_settings', [$this, 'handle_save_settings']);
        add_action('wp_ajax_acal_move_task', [$this, 'ajax_move_task']);
        add_action('wp_ajax_acal_save_tecnicos_order', [$this, 'ajax_save_tecnicos_order']);
        add_action('wp_ajax_acal_paste_task', [$this, 'ajax_paste_task']);
    }

    public function register_cpt() {
        register_post_type(self::CPT_TASK, [
            'labels' => ['name' => 'Tareas Calendario','singular_name' => 'Tarea Calendario'],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title'],
        ]);
    }

    public static function activate_plugin(){
        if (get_option(self::OPT_FRONT_SLUG, '') === '') {
            update_option(self::OPT_FRONT_SLUG, 'calendario-taller', false);
        }
        $instance = new self();
        $instance->register_front_route();
        flush_rewrite_rules();
    }

    public static function deactivate_plugin(){
        flush_rewrite_rules();
    }

    public function register_query_vars($vars){
        $vars[] = 'acal_front_mgmt';
        return $vars;
    }

    public function register_front_route(){
        $slug = $this->get_front_route_slug();
        add_rewrite_rule('^'.preg_quote($slug, '/').'/?$', 'index.php?acal_front_mgmt=1', 'top');
    }

    private function get_front_route_slug(){
        $slug = sanitize_title((string) get_option(self::OPT_FRONT_SLUG, 'calendario-taller'));
        return $slug !== '' ? $slug : 'calendario-taller';
    }

    private function is_front_management_request(){
        return (string) get_query_var('acal_front_mgmt', '') === '1';
    }

public function ajax_save_tecnicos_order(){
    if (!wp_verify_nonce($_POST['nonce'] ?? '', self::NONCE_KEY)) wp_send_json_error(['msg'=>'Nonce inválido'], 403);
    if (!$this->can_edit()) wp_send_json_error(['msg'=>'Permisos insuficientes'], 403);

    $order = isset($_POST['order']) ? (array) $_POST['order'] : [];
    // Sanitiza: solo strings/ids cortos
    $order = array_values(array_filter(array_map(function($v){
        $v = sanitize_text_field($v);
        return $v !== '' ? $v : null;
    }, $order)));

    update_option('acal_tecnicos_order', $order, false);
    wp_send_json_success(['saved' => count($order)]);
}

    public function register_admin_pages() {
        add_menu_page('Calendario Taller','Calendario Taller','read','acal_calendario',[$this,'render_calendar_page'],'dashicons-calendar-alt',25);
        add_submenu_page('acal_calendario','Técnicos','Técnicos','read','acal_tecnicos',[$this,'render_tecnicos_page']);
        add_submenu_page('acal_calendario','Importar/Exportar','Importar/Exportar','read','acal_import_export',[$this,'render_import_export_page']);
        add_submenu_page('acal_calendario','Ajustes','Ajustes','read','acal_ajustes',[$this,'render_ajustes_page']);
    }

    function enqueue_assets($hook){
        if ($this->is_restricted_context()) { return; }
        if (strpos($hook,'acal_')===false) return;

        // Base admin assets
        wp_enqueue_style('acal_admin_css', plugins_url('assets/admin.css', __FILE__), [], self::VERSION);
        wp_enqueue_script('acal_admin_js', plugins_url('assets/admin.js', __FILE__), ['jquery'], self::VERSION, true);

        // Rocket Solutions: upgrades UI/UX y parches de modal/filtros
        wp_enqueue_style('acal_rs_upgrade_css', plugins_url('assets/rs-upgrade.css', __FILE__), [], self::VERSION);
        wp_enqueue_script('acal_rs_upgrade_js', plugins_url('assets/rs-upgrade.js', __FILE__), ['jquery','acal_admin_js'], self::VERSION, true);
        wp_localize_script('acal_rs_upgrade_js', 'ACAL_ORDER', [
  'ajax'  => admin_url('admin-ajax.php'),
  'nonce' => wp_create_nonce(self::NONCE_KEY),
]);
        wp_localize_script(
             'acal_rs_upgrade_js',
             'ACAL_MOVE',
  [
    'ajax'  => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce(self::NONCE_KEY)
  ]
);

    }
    
    
public function ajax_paste_task(){
  $nonce = $_POST['nonce'] ?? '';
  if (!wp_verify_nonce($nonce, self::NONCE_KEY)) wp_send_json_error(['msg'=>'Nonce inválido'], 403);
  if (!$this->can_edit()) wp_send_json_error(['msg'=>'Permisos insuficientes'], 403);

  $src_id = isset($_POST['src_id']) ? absint($_POST['src_id']) : 0;
  $target_tecnico = isset($_POST['tecnico_id']) ? $this->sanitize_text($_POST['tecnico_id']) : '';
  $target_fecha   = $this->normalize_date($_POST['fecha'] ?? '', false);
  if (!$src_id || !$target_tecnico || !$target_fecha) wp_send_json_error(['msg'=>'Datos incompletos'], 400);
  if (get_post_type($src_id) !== self::CPT_TASK) wp_send_json_error(['msg'=>'Origen inválido'], 400);

  $estado   = get_post_meta($src_id, '_acal_estado', true);
  $sucursal = get_post_meta($src_id, '_acal_sucursal', true);
  $cliente  = get_post_meta($src_id, '_acal_cliente', true);
  $equipo   = get_post_meta($src_id, '_acal_equipo', true);
  $desc     = get_post_meta($src_id, '_acal_descripcion', true);
  $turno    = get_post_meta($src_id, '_acal_turno', true);

  $title = $cliente ? $cliente : wp_trim_words(wp_strip_all_tags($desc), 6, '…');
  if (!$title) $title = 'Tarea';

  $post_id = wp_insert_post(['post_type'=>self::CPT_TASK,'post_status'=>'publish','post_title'=>$title]);
  if (is_wp_error($post_id)) wp_send_json_error(['msg'=>'Error creando tarea'], 500);

  update_post_meta($post_id,'_acal_tecnico_id',$target_tecnico);
  update_post_meta($post_id,'_acal_fecha',$target_fecha);
  if ($estado)   update_post_meta($post_id,'_acal_estado',$estado);
  if ($sucursal) update_post_meta($post_id,'_acal_sucursal',$sucursal);
  if ($cliente)  update_post_meta($post_id,'_acal_cliente',$cliente);
  if ($equipo)   update_post_meta($post_id,'_acal_equipo',$equipo);
  if ($desc)     update_post_meta($post_id,'_acal_descripcion',$desc);
  if ($turno){ $turno = in_array(strtolower($turno), ['am','pm']) ? strtolower($turno) : ''; if($turno) update_post_meta($post_id,'_acal_turno',$turno); }

  wp_send_json_success(['msg'=>'OK','post_id'=>$post_id]);
}
    
public function ajax_move_task(){
    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, self::NONCE_KEY)) wp_send_json_error(['msg'=>'Nonce inválido'], 403);
    if (!$this->can_edit()) wp_send_json_error(['msg'=>'Permisos insuficientes'], 403);

    $task_id    = isset($_POST['task_id']) ? absint($_POST['task_id']) : 0;
    $tecnico_id = isset($_POST['tecnico_id']) ? $this->sanitize_text($_POST['tecnico_id']) : '';
    $fecha      = $this->normalize_date($_POST['fecha'] ?? '', false);

    if (!$task_id || !$fecha || !$tecnico_id) wp_send_json_error(['msg'=>'Datos incompletos'], 400);
    if (get_post_type($task_id) !== self::CPT_TASK) wp_send_json_error(['msg'=>'Tarea inválida'], 404);

    update_post_meta($task_id, '_acal_tecnico_id', $tecnico_id);
    update_post_meta($task_id, '_acal_fecha', $fecha);

    wp_send_json_success([
        'task_id'    => $task_id,
        'tecnico_id' => $tecnico_id,
        'fecha'      => $fecha,
    ]);
}


public function enqueue_assets_frontend(){
    if ($this->is_restricted_context()) { return; }
    if (is_admin()) return;

    if ($this->is_front_management_request()) {
        $this->enqueue_management_assets();
        return;
    }

    if ($this->is_standalone_request()) {
        $this->enqueue_readonly_assets();
        return;
    }

    $post = is_singular() ? get_post() : null;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'calendario_taller')) return;

    $this->enqueue_readonly_assets();
}

    private function is_standalone_request(){
        return isset($_GET['acal_standalone']) && $_GET['acal_standalone'] === '1';
    }

    private function enqueue_readonly_assets(){
        wp_enqueue_style('acal_admin_css', plugins_url('assets/admin.css', __FILE__), [], self::VERSION);
        wp_enqueue_style('acal_front_css', plugins_url('assets/front.css', __FILE__), [], self::VERSION);
        wp_enqueue_style('acal_rs_upgrade_css', plugins_url('assets/rs-upgrade.css', __FILE__), [], self::VERSION);
        wp_enqueue_script('acal_front_js', plugins_url('assets/front.js', __FILE__), ['jquery'], self::VERSION, true);
    }

    private function enqueue_management_assets(){
        wp_enqueue_style('acal_admin_css', plugins_url('assets/admin.css', __FILE__), [], self::VERSION);
        wp_enqueue_style('acal_rs_upgrade_css', plugins_url('assets/rs-upgrade.css', __FILE__), [], self::VERSION);
        wp_enqueue_script('acal_admin_js', plugins_url('assets/admin.js', __FILE__), ['jquery'], self::VERSION, true);
        wp_enqueue_script('acal_rs_upgrade_js', plugins_url('assets/rs-upgrade.js', __FILE__), ['jquery','acal_admin_js'], self::VERSION, true);
        wp_localize_script('acal_rs_upgrade_js', 'ACAL_ORDER', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_KEY),
        ]);
        wp_localize_script('acal_rs_upgrade_js', 'ACAL_MOVE', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_KEY),
        ]);
        wp_add_inline_script('acal_admin_js', 'var ajaxurl = '.wp_json_encode(admin_url('admin-ajax.php')).';', 'before');
    }


    /* Helpers */
    private function is_restricted_context(){
        // Evita interferir con login/SSO, REST/AJAX/CRON
        global $pagenow;
        if (defined('DOING_AJAX') && DOING_AJAX) return true;
        if (defined('DOING_CRON') && DOING_CRON) return true;
        if (defined('REST_REQUEST') && REST_REQUEST) return true;
        if (is_admin() && in_array($pagenow, ['wp-login.php','wp-register.php'], true)) return true;
        // Softaculous SSO
        if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], 'sapp-wp-signon.php') !== false) return true;
        return false;
    }

    private function can_edit() {
        if (current_user_can('manage_options')) return true;
        $u = wp_get_current_user(); if (!$u) return false;
        $roles = (array)$u->roles;
        return in_array('shop_manager',$roles) || current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders');
    }
    private function get_tecnicos() {
        $arr = get_option(self::OPT_TECHS, []);
        if (!is_array($arr)) $arr=[];
        usort($arr,function($a,$b){
            return strcasecmp($a['nombre']??'', $b['nombre']??'');
        });
        return $arr;
    }
    private function save_tecnicos($arr){
        update_option(self::OPT_TECHS, $arr, false);
    }
    private function sanitize_text($s){ return sanitize_text_field($s); }
    private function normalize_date($value, $allow_fallback = true) {
        $value = sanitize_text_field((string) $value);
        if ($value === '') {
            return $allow_fallback ? current_time('Y-m-d') : '';
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            $d = (int) $m[3];
            return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : ($allow_fallback ? current_time('Y-m-d') : '');
        }

        if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/', $value, $m)) {
            $d = (int) $m[1];
            $mo = (int) $m[2];
            $y = (int) $m[3];
            return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : ($allow_fallback ? current_time('Y-m-d') : '');
        }

        return $allow_fallback ? current_time('Y-m-d') : '';
    }

    private function extract_meta_scalar(array $meta_data, $key){
        if (!isset($meta_data[$key])) return '';
        $value = $meta_data[$key];
        if (is_array($value)) {
            $value = reset($value);
        }
        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value);
        }
        return sanitize_text_field((string) $value);
    }

    private function build_task_fingerprint($post_title, array $meta_data){
        $fecha = $this->normalize_date($this->extract_meta_scalar($meta_data, '_acal_fecha'), false);
        $payload = [
            'post_title'    => sanitize_text_field((string) $post_title),
            'tecnico_id'    => $this->extract_meta_scalar($meta_data, '_acal_tecnico_id'),
            'fecha'         => $fecha,
            'estado'        => $this->extract_meta_scalar($meta_data, '_acal_estado'),
            'sucursal'      => $this->extract_meta_scalar($meta_data, '_acal_sucursal'),
            'cliente'       => $this->extract_meta_scalar($meta_data, '_acal_cliente'),
            'equipo'        => $this->extract_meta_scalar($meta_data, '_acal_equipo'),
            'descripcion'   => $this->extract_meta_scalar($meta_data, '_acal_descripcion'),
            'turno'         => strtolower($this->extract_meta_scalar($meta_data, '_acal_turno')),
        ];
        return md5(wp_json_encode($payload));
    }

    private function get_tasks_total_count(){
        $counts = wp_count_posts(self::CPT_TASK);
        if (!is_object($counts)) return 0;
        $total = 0;
        foreach ((array)$counts as $status => $qty){
            if (in_array($status, ['auto-draft','trash','inherit'], true)) continue;
            $total += (int) $qty;
        }
        return $total;
    }
    private function best_text_color($bg){
        $hex = ltrim($bg,'#'); if(strlen($hex)==3){ $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        $r=hexdec(substr($hex,0,2)); $g=hexdec(substr($hex,2,2)); $b=hexdec(substr($hex,4,2));
        $yiq = (($r*299)+($g*587)+($b*114))/1000;
        return ($yiq >= 200) ? '#111' : '#fff';
    }
    /**
 * Aplica el orden global guardado a la lista de técnicos.
 * Lee la opción 'acal_tecnicos_order' (array de IDs) y reordena.
 * Cualquier técnico no presente en la opción queda al final.
 */
private function order_tecnicos_array(array $tecs): array{
    $order = get_option('acal_tecnicos_order', []);
    if (!is_array($order) || empty($order)) {
        // Si no hay orden guardado, devuelve tal cual
        return array_values($tecs);
    }

    // Índice por ID para acceso O(1)
    $byId = [];
    foreach ($tecs as $t) {
        if (!empty($t['id'])) $byId[$t['id']] = $t;
    }

    // Primero en el orden guardado
    $out = [];
    foreach ($order as $id) {
        if (isset($byId[$id])) { $out[] = $byId[$id]; unset($byId[$id]); }
    }

    // Luego los que no estaban en la opción (técnicos nuevos)
    foreach ($byId as $t) { $out[] = $t; }

    return $out;
}

    // Convierte HEX a rgba() con opacidad
    private function rgba_from_hex($hex, $alpha = 0.08){
    $h = ltrim($hex, '#');
    if (strlen($h) === 3) { $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; }
    $r = hexdec(substr($h,0,2));
    $g = hexdec(substr($h,2,2));
    $b = hexdec(substr($h,4,2));
    $a = max(0, min(1, floatval($alpha)));
    return "rgba($r,$g,$b,$a)";
}


    /* Filtros */
    private function estados_list(){
        return [
            'programado'=>'Programado',
            'en_progreso'=>'En progreso',
            'completado'=>'Completado',
            'cancelado'=>'Cancelado'
        ];
    }
    private function sucursales_list(){
        return ['Talca','Linares','Parral','Otra'];
    }
    private function get_filters(){
        return [
            'tecnico' => isset($_GET['f_tecnico']) ? sanitize_text_field($_GET['f_tecnico']) : '',
            'sucursal'=> isset($_GET['f_sucursal'])? sanitize_text_field($_GET['f_sucursal']) : '',
            'estado'  => isset($_GET['f_estado'])  ? sanitize_text_field($_GET['f_estado'])   : '',
            'search'  => isset($_GET['s'])         ? sanitize_text_field($_GET['s'])          : '',
        ];
    }
    private function task_matches_filters($meta, $filters){
        if ($filters['tecnico'] && ($meta['_acal_tecnico_id'][0] ?? '') !== $filters['tecnico']) return false;
        if ($filters['sucursal'] && strtolower($meta['_acal_sucursal'][0] ?? '') !== strtolower($filters['sucursal'])) return false;
        if ($filters['estado'] && strtolower($meta['_acal_estado'][0] ?? '') !== strtolower($filters['estado'])) return false;
        if ($filters['search']) {
            $blob = strtolower(($meta['_acal_cliente'][0] ?? '').' '.($meta['_acal_equipo'][0] ?? '').' '.($meta['_acal_descripcion'][0] ?? ''));
            if (strpos($blob, strtolower($filters['search'])) === false) return false;
        }
        return true;
    }

    private function task_array_matches_filters($task, $filters){
        if ($filters['tecnico'] && (($task['tecnico_id'] ?? '') !== $filters['tecnico'])) return false;
        if ($filters['sucursal'] && strtolower((string)($task['sucursal'] ?? '')) !== strtolower((string)$filters['sucursal'])) return false;
        if ($filters['estado'] && strtolower((string)($task['estado'] ?? '')) !== strtolower((string)$filters['estado'])) return false;

        if ($filters['search']) {
            $blob = strtolower(trim(((string)($task['cliente'] ?? '')).' '.((string)($task['equipo'] ?? '')).' '.((string)($task['descripcion'] ?? ''))));
            if (strpos($blob, strtolower((string)$filters['search'])) === false) return false;
        }

        return true;
    }

    private function redirect_to_context_or_admin($fecha){
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw((string) wp_unslash($_POST['redirect_to'])) : '';
        if ($redirect_to === '') {
            $redirect_to = wp_get_referer() ? esc_url_raw((string) wp_get_referer()) : '';
        }

        if ($redirect_to !== '') {
            $query_string = parse_url($redirect_to, PHP_URL_QUERY);
            parse_str((string) $query_string, $query_vars);
            $has_date_param = is_array($query_vars) && array_key_exists('date', $query_vars);

            if ($fecha && !$has_date_param) {
                $redirect_to = add_query_arg(['date' => $fecha], $redirect_to);
            }
            $safe = wp_validate_redirect($redirect_to, '');
            if ($safe !== '') {
                wp_safe_redirect($safe);
                exit;
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=acal_calendario&date='.urlencode($fecha)));
        exit;
    }

    private function sanitize_csv_cell($value){
        $value = is_scalar($value) ? (string) $value : '';
        $trimmed = ltrim($value, " \t\r\n");
        if ($trimmed !== '' && preg_match('/^[=+\-@]/', $trimmed)) {
            return "'".$value;
        }
        return $value;
    }

    /* Fechas / Semana */
    private function week_range_from_query(){
        $date = $this->normalize_date($_GET['date'] ?? '', true);
        $ts = strtotime($date);
        $dow = (int)date('N',$ts); // 1..7
        $monday = strtotime("-".($dow-1)." days", $ts);
        $days = [];
        for($i=0;$i<7;$i++){ $days[] = date('Y-m-d', strtotime("+$i days",$monday)); }
        return $days;
    }

    /* Tareas por semana (estructura [tecId][Y-m-d] = array de tareas) */
private function get_tasks_for_week($days){
    $start = $days[0]; $end = $days[6];

    $q = new WP_Query([
        'post_type'      => self::CPT_TASK,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_acal_fecha',
                'value'   => [$start, $end],
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ],
        ],
    ]);

    $map = [];
    if ($q->have_posts()){
        while($q->have_posts()){
            $q->the_post();
            $meta = get_post_meta(get_the_ID());
            $tec = $meta['_acal_tecnico_id'][0] ?? '';
            $fec = $meta['_acal_fecha'][0] ?? '';

            $turno = isset($meta['_acal_turno'][0]) ? strtolower($meta['_acal_turno'][0]) : '';
            $turno = in_array($turno, ['am','pm'], true) ? $turno : '';

            $arr = [
                'id'         => get_the_ID(),
                'tecnico_id' => $tec,
                'fecha'      => $fec,
                'estado'     => $meta['_acal_estado'][0] ?? 'programado',
                'sucursal'   => $meta['_acal_sucursal'][0] ?? '',
                'cliente'    => $meta['_acal_cliente'][0] ?? '',
                'equipo'     => $meta['_acal_equipo'][0] ?? '',
                'descripcion'=> $meta['_acal_descripcion'][0] ?? '',
                'turno'      => $turno,
            ];
            if ($tec && $fec){
                $map[$tec][$fec][] = $arr;
            }
        }
        wp_reset_postdata();
    }

    // ---- NUEVO: ordenar por turno (AM, PM, luego sin turno) y por título
    foreach ($map as $tecId => &$byDate){
        foreach ($byDate as $fec => &$arr){
            usort($arr, function($a,$b){
                $pa = ($a['turno']==='am') ? 0 : (($a['turno']==='pm') ? 1 : 2);
                $pb = ($b['turno']==='am') ? 0 : (($b['turno']==='pm') ? 1 : 2);
                if ($pa !== $pb) return $pa - $pb;
                $ta = strtolower(trim($a['descripcion'] ?? ''));
                $tb = strtolower(trim($b['descripcion'] ?? ''));
                return $ta <=> $tb;
            });
        }
        unset($arr);
    }
    unset($byDate);

    return $map;
}

    /* ADMIN PAGE: Calendario (L-V) */
public function render_calendar_page(){
    if (!current_user_can('read')) wp_die('No tienes permisos.');
    $days = $this->week_range_from_query();
    $renderDays = array_slice($days,0,5); // L-V fijo

    $tecnicos_raw = array_values(array_filter($this->get_tecnicos(), function($t){
        return !isset($t['activo']) || $t['activo'];
    }));
    // Usa el orden global guardado (con fallback si no existe el helper)
    $tecnicos = (method_exists($this, 'order_tecnicos_array'))
        ? $this->order_tecnicos_array($tecnicos_raw)
        : $tecnicos_raw;

    $tasks   = $this->get_tasks_for_week($days);
    $filters = $this->get_filters();
    if (!empty($filters['tecnico'])) {
        $tecnicos = array_values(array_filter($tecnicos, function($t) use ($filters){
            return isset($t['id']) && $t['id'] === $filters['tecnico'];
        }));
    }
    $can_edit = $this->can_edit();
    $now_dt = current_datetime();
    $today = $now_dt->format('Y-m-d');
    $week_current = $now_dt->modify('monday this week')->format('Y-m-d');
    $prev = date('Y-m-d', strtotime($days[0].' -7 days'));
    $next = date('Y-m-d', strtotime($days[0].' +7 days'));
    $clear_filters_url = add_query_arg([
        'f_tecnico' => false,
        'f_sucursal' => false,
        'f_estado' => false,
        's' => false,
    ]);

    $standalone_url = add_query_arg(
        [
            'acal_standalone' => '1',
            'date' => $days[0],
        ],
        home_url('/')
    );
    $frontend_management_url = home_url('/'.$this->get_front_route_slug().'/');
    $is_admin_legacy_view = is_admin() && !$this->is_front_management_request();

    echo '<div class="wrap acal-wrap">';
    echo '<h1>Calendario Taller — Vista Semanal (L-V)</h1>';

    if ($is_admin_legacy_view){
        echo '<div class="notice notice-warning acal-legacy-notice">';
        echo '<p><strong>Vista legacy (admin):</strong> la gestión principal ahora está en frontend para una mejor experiencia de uso.</p>';
        echo '<p><a class="button button-primary" href="'.esc_url($frontend_management_url).'" target="_blank" rel="noopener noreferrer">Abrir gestor frontend recomendado</a></p>';
        echo '</div>';
    }

    echo '<p><a class="button" href="'.esc_url($standalone_url).'" target="_blank" rel="noopener noreferrer">Abrir vista standalone</a></p>';

    echo '<form method="get" class="acal-topbar">';
    echo '<input type="hidden" name="page" value="acal_calendario" />';
    echo '<div class="acal-nav">';
    echo '<a class="button" href="'.esc_url(add_query_arg(['date'=>$prev])).'">&laquo; Semana anterior</a> ';
    echo '<label>Semana de: <input type="date" name="date" value="'.esc_attr($days[0]).'" /></label> ';
    echo '<button class="button">Ir</button> ';
    echo '<a class="button" href="'.esc_url(add_query_arg(['date'=>$next])).'">Próxima semana &raquo;</a>';
    echo '</div>';

    echo '<div class="acal-quick-actions" aria-label="Acciones rápidas">';
    echo '<a class="button" href="'.esc_url(add_query_arg(['date'=>$today])).'">Hoy</a>';
    echo '<a class="button" href="'.esc_url(add_query_arg(['date'=>$week_current])).'">Semana actual</a>';
    echo '<a class="button" href="'.esc_url($clear_filters_url).'">Limpiar filtros</a>';
    echo '</div>';

    echo '<div class="acal-filters">';
    echo '<label>Técnico: <select name="f_tecnico"><option value="">Todos</option>';
    foreach ($tecnicos as $t){
        $sel = $filters['tecnico']===$t['id'] ? 'selected' : '';
        echo '<option value="'.esc_attr($t['id']).'" '.$sel.'>'.esc_html($t['nombre']).'</option>';
    }
    echo '</select></label> ';
    $sucursales = $this->sucursales_list();
    echo '<label>Sucursal: <select name="f_sucursal"><option value="">Todas</option>';
    foreach ($sucursales as $s){
        $sel = strtolower($filters['sucursal'])===strtolower($s) ? 'selected' : '';
        echo '<option value="'.esc_attr($s).'" '.$sel.'>'.esc_html($s).'</option>';
    }
    echo '</select></label> ';
    echo '<label>Estado: <select name="f_estado"><option value="">Todos</option>';
    foreach ($this->estados_list() as $k=>$v){
        $sel = strtolower($filters['estado'])===strtolower($k)?'selected':'';
        echo '<option value="'.esc_attr($k).'" '.$sel.'>'.esc_html($v).'</option>';
    }
    echo '</select></label> ';
    echo '<label>Buscar: <input type="search" name="s" value="'.esc_attr($filters['search']).'" placeholder="Cliente, equipo, texto..." /></label> ';
    echo '<button class="button button-primary">Filtrar</button>';
    echo '</div>';
    echo '</form>';

    echo '<div class="acal-legend">';
    foreach ($tecnicos as $t){
        $fg=$this->best_text_color($t['color']);
        echo '<span class="acal-pill" style="--pill-bg:'.esc_attr($t['color']).';color:'.esc_attr($fg).'">'.esc_html($t['nombre']).'</span>';
    }
    echo '</div>';

    echo '<div class="acal-grid">';
    // Cabecera (vacía + días)
    echo '<div class="acal-cell acal-head acal-tech-col">&nbsp;</div>';
    foreach ($renderDays as $d){
        $label = date_i18n('D d/m', strtotime($d));
        $exp = wp_nonce_url(
            add_query_arg(array_merge($_GET,['action'=>'acal_export_day_png','date'=>$d ]), admin_url('admin-post.php')),
            self::NONCE_KEY
        );
        echo '<div class="acal-cell acal-head"><span>'.esc_html(ucfirst($label)).'</span> <a class="button acal-export" href="'.esc_url($exp).'">Exportar PNG</a></div>';
    }

    // Filas por técnico
    foreach ($tecnicos as $t){
        $tecId = $t['id'];
$color = $t['color'];
$fg    = $this->best_text_color($color);

echo '<div class="acal-cell acal-tech-col acal-tech-item" data-tecnico-id="'.esc_attr($t['id']).'">';

// pastilla con nombre + flechas adentro
echo   '<div class="acal-techname" style="background:'.esc_attr($color).';color:'.esc_attr($fg).'">';

if ($can_edit){
  echo   '<button type="button" class="acal-order-btn acal-move-up" title="Subir" aria-label="Subir">↑</button>';
}

echo     '<span class="acal-techlabel">'.esc_html($t['nombre']).'</span>';

if ($can_edit){
  echo   '<button type="button" class="acal-order-btn acal-move-down" title="Bajar" aria-label="Bajar">↓</button>';
}

echo   '</div>'; // .acal-techname
echo '</div>';   // .acal-tech-item


        // === Celdas de los días ===
        foreach ($renderDays as $d){
            $bg = $this->rgba_from_hex($color, 0.08); // fondo suave por técnico
            echo '<div class="acal-cell" style="background:'.esc_attr($bg).'">';

            $cellTasks = $tasks[$tecId][$d] ?? [];
            if (!empty($filters['sucursal']) || !empty($filters['estado']) || !empty($filters['search']) || !empty($filters['tecnico'])) {
                $cellTasks = array_values(array_filter($cellTasks, function($task) use ($filters){
                    return $this->task_array_matches_filters($task, $filters);
                }));
            }
            if ($can_edit){
                echo '<button class="button acal-add acal-hide-on-print" data-tecnico="'.esc_attr($tecId).'" data-date="'.esc_attr($d).'" data-tecnico-name="'.esc_attr($t['nombre']).'" aria-label="Agregar tarea para '.esc_attr($t['nombre']).' el '.esc_attr($d).'">+ Agregar</button>';
                echo '<button class="button acal-paste" data-date="'.esc_attr($d).'" data-tecnico="'.esc_attr($tecId).'" style="margin-left:6px;display:none" aria-label="Pegar tarea en '.esc_attr($t['nombre']).' el '.esc_attr($d).'">Pegar</button>';
            }
            if (!empty($cellTasks)){
                foreach($cellTasks as $task){
                    $title   = trim($task['descripcion']);
                    $cliente = trim($task['cliente']);
                    $equipo  = trim($task['equipo']);
                    $estado  = $task['estado'];
                    $suc     = $task['sucursal'];

                    $metaLine=[]; if($cliente) $metaLine[]=$cliente; if($equipo) $metaLine[]=$equipo; if($suc) $metaLine[]=$suc;
                    $body=implode(' · ',$metaLine);

                    echo '<div class="acal-task" style="border-left:6px solid '.esc_attr($color).'" data-task-id="'.esc_attr($task['id']).'">';
                    echo   '<div class="acal-task-title">'.esc_html($title ?: '(Sin descripción)').'</div>';

                    // Pill AM/PM (si existe)
                    if (!empty($task['turno'])){
                        echo '<div class="acal-task-meta"><span class="acal-turno '.esc_attr($task['turno']).'">'.esc_html(strtoupper($task['turno'])).'</span></div>';
                    }

                    if ($body) echo '<div class="acal-task-meta">'.esc_html($body).'</div>';

                    // (ELIMINADO) Badge de estado en tarjeta (se pidió no mostrar)
                    // echo '<div class="acal-task-badges"><span class="badge">'.esc_html($this->estados_list()[$estado] ?? $estado).'</span></div>';

                    if ($can_edit){
                        echo '<div class="acal-kebab-wrap">';
                        echo   '<button class="button acal-kebab" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de tarea" aria-controls="acal-menu-'.esc_attr($task['id']).'">&#8942;</button>';
                        echo   '<div id="acal-menu-'.esc_attr($task['id']).'" class="acal-menu" role="menu" style="display:none">';
                        echo '<a href="#" class="acal-menu-link acal-copy" role="menuitem" data-task-id="'.esc_attr($task['id']).'">Copiar</a>';
                        echo     '<a href="#" class="acal-edit" role="menuitem" data-task=\''.esc_attr(wp_json_encode($task)).'\' data-tecnico-name="'.esc_attr($t['nombre']).'">Editar</a>';
                        echo     '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="acal-menu-form" onsubmit="return confirm(\'¿Eliminar tarea?\')">';
                        echo       '<input type="hidden" name="action" value="acal_delete_task" />';
                        echo       '<input type="hidden" name="task_id" value="'.esc_attr($task['id']).'" />';
                        $current_url = (is_ssl() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
                        echo       '<input type="hidden" name="redirect_to" value="'.esc_url($current_url).'" />';
                        echo       '<input type="hidden" name="_wpnonce" value="'.esc_attr(wp_create_nonce(self::NONCE_KEY)).'" />';
                        echo       '<button type="submit" class="acal-menu-link" role="menuitem">Eliminar</button>';
                        echo     '</form>';
                        echo   '</div>';
                        echo '</div>';
                    }
                    echo '</div>'; // .acal-task
                }
            }
            echo '</div>'; // .acal-cell (día)
        }
    }
    echo '</div>'; // .acal-grid
        echo '<script>window.ACAL_NONCE="'.esc_js(wp_create_nonce(self::NONCE_KEY)).'";</script>';

    // Modal
    $nonce = wp_create_nonce(self::NONCE_KEY);
    echo '<div id="acal-modal" class="acal-modal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="acal-modal-title" aria-hidden="true"><div class="acal-modal-content">';
    echo '<button type="button" class="acal-modal-close" aria-label="Cerrar modal">&times;</button><h2 id="acal-modal-title">Nueva tarea</h2>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" id="acal-form">';
    $current_url = (is_ssl() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
    echo '<input type="hidden" name="action" value="acal_create_task" id="acal-action" />';
    echo '<input type="hidden" name="task_id" id="acal-task-id" value="" />';
    echo '<input type="hidden" name="redirect_to" id="acal-redirect-to" value="'.esc_url($current_url).'" />';
    // Hidden para turno (lo completa el JS)
    echo '<input type="hidden" name="turno" id="acal-turno-hidden" value="" />';
    echo '<label>Técnico<br><select name="tecnico_id" id="acal-tecnico-id" required>';
    foreach($tecnicos as $t){ echo '<option value="'.esc_attr($t['id']).'">'.esc_html($t['nombre']).'</option>'; } echo '</select></label>';
    echo '<label>Fecha<br><input type="date" name="fecha" id="acal-fecha" required /></label>';
    echo '<label>Estado<br><select name="estado" id="acal-estado">';
    foreach ($this->estados_list() as $k=>$v){ echo '<option value="'.esc_attr($k).'">'.esc_html($v).'</option>'; } echo '</select></label>';
    $sucs = $this->sucursales_list();
    echo '<label>Lugar<br><select name="sucursal" id="acal-sucursal"><option value="">—</option>';
    foreach ($sucs as $s){ echo '<option value="'.esc_attr($s).'">'.esc_html($s).'</option>'; } echo '</select></label>';
    echo '<label>Cliente<br><input type="text" name="cliente" id="acal-cliente" /></label>';
    echo '<label>Equipo/Modelo<br><input type="text" name="equipo" id="acal-equipo" /></label>';
    // SIN placeholder en Descripción
    echo '<label>Descripción<br><textarea name="descripcion" id="acal-descripcion"></textarea></label>';
    echo '<div class="acal-form-actions"><button class="button button-primary">Guardar</button></div>';
    echo '</form></div></div>';

    echo '</div>'; // wrap
}

    public function render_tecnicos_page(){
        if (!current_user_can('read')) wp_die('No tienes permisos.');
        $can_edit=$this->can_edit(); $nonce=wp_create_nonce(self::NONCE_KEY); $tecnicos=$this->get_tecnicos();
        echo '<div class="wrap"><h1>Técnicos</h1>';
        if ($can_edit){
            echo '<h2>Agregar técnico</h2>';
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="acal-tech-form">';
            echo '<input type="hidden" name="action" value="acal_add_tech" />';
            echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
            echo '<label>Nombre<br><input type="text" name="nombre" required /></label> ';
            echo '<label>Color (HEX)<br><input type="text" name="color" class="regular-text acal-hex" value="" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" placeholder="#RRGGBB" /> ';
            echo 'Activo <select name="activo"><option value="1" selected>Sí</option><option value="0">No</option></select> ';
            echo '<button class="button">Agregar</button></form>';
        }
        echo '<h2>Listado</h2>';
        echo '<div class="acal-techlist"><table class="widefat striped"><thead><tr><th>Nombre</th><th>Color</th><th>Activo</th><th>Acciones</th></tr></thead><tbody>';
        if (!empty($tecnicos)){
            foreach($tecnicos as $t){
                echo '<tr><td>'.esc_html($t['nombre']).'</td>';
                echo '<td><span class="acal-pill" style="--pill-bg:'.esc_attr($t['color']).'">'.esc_html($t['color']).'</span></td>';
                echo '<td>'.( (!isset($t['activo']) || $t['activo']) ? 'Sí' : 'No' ).'</td>';
                echo '<td>';
                if ($can_edit){
                    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin-right:8px">';
                    echo '<input type="hidden" name="action" value="acal_update_tech" />';
                    echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
                    echo '<input type="hidden" name="id" value="'.esc_attr($t['id']).'" />';
                    echo 'Nombre <input type="text" name="nombre" value="'.esc_attr($t['nombre']).'" /> ';
                    echo 'Color <input type="text" name="color" class="regular-text acal-hex" value="'.esc_attr($t['color']).'" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" placeholder="#RRGGBB" /> ';
                    echo 'Activo <select name="activo"><option value="1" '.((!isset($t['activo']) || $t['activo'])?'selected':'').'>Sí</option><option value="0" '.((isset($t['activo']) && !$t['activo'])?'selected':'').'>No</option></select> ';
                    echo '<button class="button">Actualizar</button>';
                    echo '</form> ';
                    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="inline" onsubmit="return confirm(\'¿Eliminar técnico? (No borra tareas existentes)\')">';
                    echo '<input type="hidden" name="action" value="acal_delete_tech" />';
                    echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
                    echo '<input type="hidden" name="id" value="'.esc_attr($t['id']).'" />';
                    echo '<button class="button button-link-delete">Eliminar</button>';
                    echo '</form>';
                } else { echo '<em>Solo lectura</em>'; }
                echo '</td></tr>';
            }
        }
        echo '</tbody></table></div>';
    }

    public function render_import_export_page(){
        if (!current_user_can('read')) wp_die('No tienes permisos.');
        $can_edit = $this->can_edit();
        $nonce = wp_create_nonce(self::NONCE_KEY);
        $current_tasks = $this->get_tasks_total_count();
        $tecnicos_count = count($this->get_tecnicos());
        $tecnicos = $this->order_tecnicos_array(array_values(array_filter($this->get_tecnicos(), function($t){
            return !empty($t['id']) && !empty($t['nombre']);
        })));

        $import_done = isset($_GET['import']) && $_GET['import'] === '1';
        $stats = [
            'tasks_total' => isset($_GET['tasks_total']) ? absint($_GET['tasks_total']) : 0,
            'tasks_added' => isset($_GET['tasks_added']) ? absint($_GET['tasks_added']) : 0,
            'tasks_skipped' => isset($_GET['tasks_skipped']) ? absint($_GET['tasks_skipped']) : 0,
            'tasks_errors' => isset($_GET['tasks_errors']) ? absint($_GET['tasks_errors']) : 0,
            'tecs_total' => isset($_GET['tecs_total']) ? absint($_GET['tecs_total']) : 0,
            'tecs_added' => isset($_GET['tecs_added']) ? absint($_GET['tecs_added']) : 0,
            'tecs_skipped' => isset($_GET['tecs_skipped']) ? absint($_GET['tecs_skipped']) : 0,
        ];

        echo '<div class="wrap"><h1>Importar / Exportar</h1>';
        echo '<p>Exporta o importa datos del calendario (tareas y técnicos).</p>';
        echo '<p><strong>Tareas actuales:</strong> '.esc_html((string)$current_tasks).' · <strong>Técnicos actuales:</strong> '.esc_html((string)$tecnicos_count).'</p>';

        if ($import_done){
            echo '<div class="notice notice-success"><p><strong>Importación finalizada.</strong> '
                .'Tareas archivo: '.esc_html((string)$stats['tasks_total'])
                .', agregadas: '.esc_html((string)$stats['tasks_added'])
                .', omitidas: '.esc_html((string)$stats['tasks_skipped'])
                .', errores: '.esc_html((string)$stats['tasks_errors'])
                .'. Técnicos archivo: '.esc_html((string)$stats['tecs_total'])
                .', agregados: '.esc_html((string)$stats['tecs_added'])
                .', omitidos: '.esc_html((string)$stats['tecs_skipped']).'.</p></div>';

            $log_key = isset($_GET['import_log']) ? sanitize_text_field($_GET['import_log']) : '';
            $log = $log_key ? get_transient('acal_import_log_'.$log_key) : [];
            if (is_array($log) && !empty($log['allow_duplicates'])){
                echo '<div class="notice notice-info"><p>Importación ejecutada con opción: <strong>Permitir duplicadas</strong>.</p></div>';
            }

            if (is_array($log) && !empty($log['reasons'])){
                echo '<div class="notice notice-info"><p><strong>Motivos de tareas omitidas:</strong> ';
                $parts = [];
                foreach ($log['reasons'] as $rk => $rv){
                    $parts[] = esc_html($rk).': '.esc_html((string)absint($rv));
                }
                echo implode(' · ', $parts).'</p>';
                if (!empty($log['samples']) && is_array($log['samples'])){
                    echo '<ul style="margin:6px 0 0 18px;list-style:disc;">';
                    foreach ($log['samples'] as $item){
                        $t = isset($item['title']) ? (string)$item['title'] : '(Sin título)';
                        $r = isset($item['reason']) ? (string)$item['reason'] : 'omitida';
                        $f = isset($item['fecha']) ? (string)$item['fecha'] : '';
                        echo '<li><strong>'.esc_html($t).'</strong> — '.esc_html($r).($f!=='' ? ' ('.esc_html($f).')' : '').'</li>';
                    }
                    echo '</ul>';
                }
                echo '</div>';
                delete_transient('acal_import_log_'.$log_key);
            }
        }

        if (isset($_GET['purge']) && $_GET['purge'] === '1'){
            $deleted = isset($_GET['deleted']) ? absint($_GET['deleted']) : 0;
            echo '<div class="notice notice-warning"><p><strong>Sanitización completada.</strong> '
                .'Tareas eliminadas: '.esc_html((string)$deleted).'.</p></div>';
        }

        echo '<h2>Exportar</h2>';
        echo '<p>La exportación incluye un bloque <code>summary</code> con conteos y metadatos del proceso.</p>';
        if ($can_edit){
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            echo '<input type="hidden" name="action" value="acal_export_data" />';
            echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
            echo '<button class="button button-primary">Descargar exportación</button>';
            echo '</form>';

            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="margin-top:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">';
            echo '<input type="hidden" name="action" value="acal_export_data" />';
            echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
            echo '<input type="hidden" name="export_format" value="csv" />';
            echo '<label for="acal-export-tecnico"><strong>Exportar CSV por técnico:</strong></label>';
            echo '<select id="acal-export-tecnico" name="tecnico_id" required>';
            echo '<option value="">Selecciona técnico</option>';
            foreach ($tecnicos as $t){
                echo '<option value="'.esc_attr($t['id']).'">'.esc_html($t['nombre']).'</option>';
            }
            echo '</select>';
            echo '<button class="button button-secondary">Descargar CSV</button>';
            echo '</form>';
        } else {
            echo '<p><em>Solo lectura</em></p>';
        }

        echo '<h2>Sanitizar</h2>';
        echo '<p>Elimina todas las tareas del calendario para iniciar desde cero.</p>';
        if ($can_edit){
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" onsubmit="return confirm(&quot;¿Eliminar TODAS las tareas? Esta acción no se puede deshacer.&quot;)">';
            echo '<input type="hidden" name="action" value="acal_purge_tasks" />';
            echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
            echo '<p><button class="button button-secondary">Eliminar todas las tareas</button></p>';
            echo '</form>';
        } else {
            echo '<p><em>Solo lectura</em></p>';
        }

        echo '<h2>Importar</h2>';
        echo '<p>En modo normal, solo agrega faltantes (sin duplicar registros existentes).</p>';
        if ($can_edit){
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" enctype="multipart/form-data">';
            echo '<input type="hidden" name="action" value="acal_import_data" />';
            echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($nonce).'" />';
            echo '<p><input type="file" name="acal_import_file" accept="application/json" required /></p>';
            echo '<label><input type="checkbox" name="acal_replace" value="1" /> Reemplazar tareas existentes</label><br>';
            echo '<label><input type="checkbox" name="acal_allow_duplicates" value="1" /> Permitir importar tareas duplicadas</label>';
            echo '<p><button class="button button-primary">Importar</button></p>';
            echo '</form>';
        } else {
            echo '<p><em>Solo lectura</em></p>';
        }
        echo '</div>';
    }

    public function render_ajustes_page(){
        if (!current_user_can('read')) wp_die('No tienes permisos.');
        $days = $this->week_range_from_query();
        $settings_nonce = wp_create_nonce(self::NONCE_KEY);
        $front_slug = $this->get_front_route_slug();
        $front_url = home_url('/'.$front_slug.'/');
        $standalone_calendar = add_query_arg(
            [
                'acal_standalone' => '1',
                'date' => $days[0],
            ],
            home_url('/')
        );

        echo '<div class="wrap"><h1>Ajustes</h1>';
        if (isset($_GET['saved']) && $_GET['saved'] === '1') {
            echo '<div class="notice notice-success"><p>Ajustes guardados.</p></div>';
        }
        echo '<h2>Ruta de gestión frontend</h2>';
        echo '<p>Configura la ruta para gestionar el calendario desde frontend sin depender del theme ni shortcode.</p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="acal_save_settings" />';
        echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($settings_nonce).'" />';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="acal-front-slug">Slug de ruta</label></th>';
        echo '<td><input type="text" id="acal-front-slug" name="front_slug" value="'.esc_attr($front_slug).'" class="regular-text" required pattern="[a-z0-9\-]+" />';
        echo '<p class="description">URL actual: <code>'.esc_html($front_url).'</code></p></td></tr>';
        echo '</tbody></table>';
        if ($this->can_edit()) {
            echo '<p><button class="button button-primary">Guardar ruta</button></p>';
        } else {
            echo '<p><em>Solo lectura</em></p>';
        }
        echo '</form>';

        echo '<h2>Vista standalone</h2>';
        echo '<p>Abre el calendario en frontend sin theme (solo lectura).</p>';
        echo '<ul>';
        echo '<li><a class="button" href="'.esc_url($standalone_calendar).'" target="_blank" rel="noopener noreferrer">Abrir Calendario Taller</a></li>';
        echo '</ul>';
        echo '</div>';
    }

    public function handle_save_settings(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');

        $slug = sanitize_title((string) ($_POST['front_slug'] ?? ''));
        if ($slug === '') {
            $slug = 'calendario-taller';
        }

        update_option(self::OPT_FRONT_SLUG, $slug, false);

        // Registra la regla con el nuevo slug dentro del mismo request antes de flush
        add_rewrite_rule('^'.preg_quote($slug, '/').'/?$', 'index.php?acal_front_mgmt=1', 'top');
        flush_rewrite_rules();

        wp_safe_redirect(admin_url('admin.php?page=acal_ajustes&saved=1'));
        exit;
    }

    /* Crear / actualizar / eliminar tareas */
    public function handle_create_task(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');
        $tecnico_id = $this->sanitize_text($_POST['tecnico_id'] ?? '');
        $fecha = $this->normalize_date($_POST['fecha'] ?? '', true);
        $estado = $this->sanitize_text($_POST['estado'] ?? 'programado');
        $sucursal = $this->sanitize_text($_POST['sucursal'] ?? '');
        $cliente = $this->sanitize_text($_POST['cliente'] ?? '');
        $equipo  = $this->sanitize_text($_POST['equipo'] ?? '');
        $desc    = $this->sanitize_text($_POST['descripcion'] ?? '');

        if (!$tecnico_id) wp_die('Técnico requerido');
        $title = $cliente ? $cliente : wp_trim_words(wp_strip_all_tags($desc),6,'…'); if(!$title) $title='Tarea';
        $post_id = wp_insert_post(['post_type'=>self::CPT_TASK,'post_status'=>'publish','post_title'=>$title]);
        if (is_wp_error($post_id)) wp_die('Error creando tarea');
        update_post_meta($post_id,'_acal_tecnico_id',$tecnico_id);
        update_post_meta($post_id,'_acal_fecha',$fecha);
        update_post_meta($post_id,'_acal_estado',$estado);
        update_post_meta($post_id,'_acal_sucursal',$sucursal);
        update_post_meta($post_id,'_acal_cliente',$cliente);
        update_post_meta($post_id,'_acal_equipo',$equipo);
        update_post_meta($post_id,'_acal_descripcion',$desc);

        $turno = isset($_POST['turno']) ? sanitize_text_field($_POST['turno']) : 'am';
        $turno = in_array(strtolower($turno), ['am','pm'], true) ? strtolower($turno) : 'am';
        update_post_meta($post_id, '_acal_turno', $turno);

        $this->redirect_to_context_or_admin($fecha);
    }
public function handle_update_task(){
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
    if (!$this->can_edit()) wp_die('Permisos insuficientes');

    $post_id    = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    if (!$post_id) wp_die('ID inválido');

    $tecnico_id = $this->sanitize_text($_POST['tecnico_id'] ?? '');
    $fecha      = $this->normalize_date($_POST['fecha'] ?? '', true);
    $estado     = $this->sanitize_text($_POST['estado'] ?? 'programado');
    $sucursal   = $this->sanitize_text($_POST['sucursal'] ?? '');
    $cliente    = $this->sanitize_text($_POST['cliente'] ?? '');
    $equipo     = $this->sanitize_text($_POST['equipo'] ?? '');
    $desc       = $this->sanitize_text($_POST['descripcion'] ?? '');

    if ($tecnico_id){
        update_post_meta($post_id, '_acal_tecnico_id', $tecnico_id);
    }
    update_post_meta($post_id, '_acal_fecha',       $fecha);
    update_post_meta($post_id, '_acal_estado',      $estado);
    update_post_meta($post_id, '_acal_sucursal',    $sucursal);
    update_post_meta($post_id, '_acal_cliente',     $cliente);
    update_post_meta($post_id, '_acal_equipo',      $equipo);
    update_post_meta($post_id, '_acal_descripcion', $desc);

    // ---- NUEVO: turno AM/PM ----
    $turno = isset($_POST['turno']) ? sanitize_text_field($_POST['turno']) : '';
    $turno = in_array(strtolower($turno), ['am','pm'], true) ? strtolower($turno) : '';
    if ($turno !== '') {
        update_post_meta($post_id, '_acal_turno', $turno);
    } else {
        delete_post_meta($post_id, '_acal_turno');
    }

    // Actualiza título si corresponde (opcional)
    $title = $cliente ? $cliente : wp_trim_words(wp_strip_all_tags($desc), 6, '…');
    if(!$title) $title = 'Tarea';
    wp_update_post([ 'ID'=>$post_id, 'post_title'=>$title ]);

    $this->redirect_to_context_or_admin($fecha);
}
    public function handle_delete_task(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');
        $task_id = intval($_POST['task_id'] ?? 0);
        if ($task_id) wp_delete_post($task_id, true);

        $fecha = $this->normalize_date($_REQUEST['date'] ?? '', true);
        $this->redirect_to_context_or_admin($fecha);
    }

    /* Exportar columna del día como PNG (admin) */

public function handle_export_day_png(){
    if (!current_user_can('read')) wp_die('Sin permisos');
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');

    $date = $this->normalize_date($_GET['date'] ?? '', true);
    $filters   = $this->get_filters();
    $tecnicos_raw = array_values(array_filter($this->get_tecnicos(), function($t){
        return !isset($t['activo']) || $t['activo'];
    }));
    $tecnicos = $this->order_tecnicos_array($tecnicos_raw);

    // Tareas del día agrupadas por técnico
    $q = new WP_Query([
        'post_type'      => self::CPT_TASK,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => '_acal_fecha',
                'value'   => $date,
                'compare' => '=',
                'type'    => 'DATE'
            ]
        ]
    ]);

    $map = [];
    if ($q->have_posts()){
        while($q->have_posts()){
            $q->the_post();
            $id   = get_the_ID();
            $meta = get_post_meta($id);
            if (!$this->task_matches_filters($meta, $filters)) continue;

            $tec = $meta['_acal_tecnico_id'][0] ?? '';
            if(!$tec) continue;

            $map[$tec][] = [
                'id'          => $id,
                'title'       => get_the_title($id),
                'cliente'     => $meta['_acal_cliente'][0] ?? '',
                'equipo'      => $meta['_acal_equipo'][0] ?? '',
                'sucursal'    => $meta['_acal_sucursal'][0] ?? '',
                'descripcion' => $meta['_acal_descripcion'][0] ?? '',
                'turno'       => $meta['_acal_turno'][0] ?? '',   // AM / PM
            ];
        }
        wp_reset_postdata();
    }

    $date_label = date_i18n('l d/m/Y', strtotime($date));
    $title = 'Programación diaria — '.$date_label;
    $fname = 'programacion-'.date_i18n('Y-m-d', strtotime($date)).'.png';

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>'.esc_html($title).'</title>';

    echo '<style>
        *{box-sizing:border-box}
        body{
            font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
            background:#fff; margin:10px; color:#111;
        }
        .toolbar{display:flex;justify-content:flex-end;margin-bottom:8px}
        .toolbar button{padding:6px 10px;font-size:12px;cursor:pointer}

        #acal-export-wrap{
            border:1px solid #e7e9ef;
            border-radius:10px;
            overflow:hidden;
            background:#fff;
            display:inline-block;
            width:fit-content;
        }

        .acal-export-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:10px 12px;
            border-bottom:1px solid #eef1f5;
        }
        .acal-export-title{
            font-size:16px;
            font-weight:900;
            letter-spacing:.2px;
            display:flex;
            align-items:baseline;
            gap:10px;
        }
        .acal-export-title .date{
            font-size:12px;
            font-weight:800;
            color:#4b5563;
            text-transform:capitalize;
            white-space:nowrap;
        }

        #acal-export-day{
            display:grid;
            grid-template-columns: 150px auto;
            background:#fff;
        }

        .tcell, .dcell{ border-bottom:1px solid #eef1f5; }
        .tcell{
            background:#f7f8fa;
            padding:6px;
        }
        .techname{
            width:100%;
            text-align:center;
            padding:8px 8px;
            border-radius:12px;
            font-weight:800;
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:.4px;
            box-shadow:0 3px 8px rgba(0,0,0,.06), inset 0 -1px 0 rgba(255,255,255,.15);
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .dcell{
            padding:6px 8px;
            color:#111 !important;
        }

        .group{
            margin:0 0 6px 0;
        }
        .group:last-child{ margin-bottom:0; }
        .group-title{
            display:inline-block;
            font-weight:900;
            font-size:10px;
            padding:2px 8px;
            border-radius:999px;
            text-transform:uppercase;
            letter-spacing:.4px;
            color:#374151;
            background:rgba(0,0,0,.05);
            margin:0 0 4px 0;
        }

        .task{
            /* IMPORTANTE: evitar -webkit-text-fill-color (puede dejar texto invisible en html2canvas) */
            color:#111 !important;
            display:block;
            width:max-content;
            max-width:100%;
            background:#fff;
            border:1px solid #eaecef;
            border-left-width:5px;
            border-radius:8px;
            padding:6px 8px;
            margin:0 0 4px 0;
            box-shadow:0 1px 2px rgba(0,0,0,.03);
            vertical-align:top;
        }
        .task .t{
            color:#111 !important;
            font-weight:700;
            font-size:12px;
            margin:0 0 1px 0;
            line-height:1.15;
        }
        .task .m{
            color:#6b7280 !important;
            font-size:11px;
            line-height:1.2;
        }

        .empty{
            color:#9ca3af;
            font-size:11px;
            font-style:italic;
        }
        .muted{color:#555;font-size:12px;margin-bottom:8px}
    </style>';
    echo '</head><body>';

    echo '<div class="toolbar"><button id="dl">Descargar PNG</button></div>';

    // Contenedor que se captura (incluye fecha en el PNG)
    echo '<div id="acal-export-wrap">';
    echo '<div class="acal-export-top">'
        .'<div class="acal-export-title">Programación diaria <span class="date">'.esc_html($date_label).'</span></div>'
        .'</div>';

    echo '<div id="acal-export-day">';

    foreach ($tecnicos as $t){
        // Exportación diaria: el mapa se indexa solo por técnico.
        $list = $map[$t['id']] ?? [];

        if (!empty($list)){
            usort($list, function($a, $b){
                $order = ['AM' => 0, 'PM' => 1];
                $aTurno = strtoupper(trim($a['turno'] ?? ''));
                $bTurno = strtoupper(trim($b['turno'] ?? ''));
                $aVal = $order[$aTurno] ?? 99;
                $bVal = $order[$bTurno] ?? 99;
                if ($aVal !== $bVal) return $aVal - $bVal;
                $aId = (int)($a['id'] ?? 0);
                $bId = (int)($b['id'] ?? 0);
                if ($aId === $bId) return 0;
                return ($aId < $bId) ? -1 : 1;
            });
        }

        // Separar AM / PM, pero se mostrarán en UNA SOLA columna (vertical)
        $am = [];
        $pm = [];
        foreach ($list as $task){
            $turno = strtoupper(trim($task['turno'] ?? ''));
            if ($turno === 'PM') $pm[] = $task;
            else $am[] = $task; // default AM
        }

        $fg = $this->best_text_color($t['color']);
        $bg = $this->rgba_from_hex($t['color'], 0.08);

        echo '<div class="tcell"><div class="techname" style="background:'.esc_attr($t['color']).';color:'.esc_attr($fg).'">'
              .esc_html($t['nombre']).'</div></div>';

        echo '<div class="dcell" style="background:'.esc_attr($bg).'">';

        $hasAny = (!empty($am) || !empty($pm));

        if ($hasAny){
            if (!empty($am)){
                echo '<div class="group"><div class="group-title">AM</div><div>';
                foreach($am as $task){
                    $titleTxt = trim((string)($task['descripcion'] ?? ''));
                    if ($titleTxt === '') $titleTxt = trim((string)($task['title'] ?? ''));
                    if ($titleTxt === '') $titleTxt = '(Sin descripción)';

                    $metaTxt = implode(' · ', array_filter([
                        $task['cliente']  ?: '',
                        $task['equipo']   ?: '',
                        $task['sucursal'] ?: ''
                    ]));

                    echo '<div class="task" style="border-left-color:'.esc_attr($t['color']).'">';
                    echo '<div class="t">'.esc_html($titleTxt).'</div>';
                    if($metaTxt) echo '<div class="m">'.esc_html($metaTxt).'</div>';
                    echo '</div>';
                }
                echo '</div></div>';
            }

            if (!empty($pm)){
                echo '<div class="group"><div class="group-title">PM</div><div>';
                foreach($pm as $task){
                    $titleTxt = trim((string)($task['descripcion'] ?? ''));
                    if ($titleTxt === '') $titleTxt = trim((string)($task['title'] ?? ''));
                    if ($titleTxt === '') $titleTxt = '(Sin descripción)';

                    $metaTxt = implode(' · ', array_filter([
                        $task['cliente']  ?: '',
                        $task['equipo']   ?: '',
                        $task['sucursal'] ?: ''
                    ]));

                    echo '<div class="task" style="border-left-color:'.esc_attr($t['color']).'">';
                    echo '<div class="t">'.esc_html($titleTxt).'</div>';
                    if($metaTxt) echo '<div class="m">'.esc_html($metaTxt).'</div>';
                    echo '</div>';
                }
                echo '</div></div>';
            }
        } else {
            echo '<div class="empty">—</div>';
        }

        echo '</div>'; // dcell
    }

    echo '</div>'; // export-day
    echo '</div>'; // export-wrap

    // JS: html2canvas export
    $fname_js = esc_js($fname);
    $h2c = esc_url( plugins_url('assets/html2canvas.min.js', __FILE__) . '?ver=' . self::VERSION );
    echo <<<ACALJS
<script src="$h2c"></script>
<script>
(function(){
  function exportPNG(){
    var wrap = document.getElementById("acal-export-wrap");
    if(!wrap){ alert("No se encontró el contenedor de exportación."); return; }

    // Evitar capturas "corridas" por scroll/offsets del viewport
    var prevX = window.scrollX || window.pageXOffset || 0;
    var prevY = window.scrollY || window.pageYOffset || 0;
    if(prevX || prevY){
      window.scrollTo(0,0);
    }
    // Diagnóstico rápido (se ve en Consola)
    try{
      var first = wrap.querySelector('.task .t');
      console.log('[acal-export] first text:', first ? first.textContent : '(none)');
      console.log('[acal-export] first color:', first ? getComputedStyle(first).color : '(n/a)');
    }catch(e){ /* noop */ }

    var box = null;
    var scale = 2;

    function doCapture(extra){
      var opts = {
        backgroundColor: "#ffffff",
        scale: scale,
        width: Math.ceil(box.width),
        height: Math.ceil(box.height),
        windowWidth: Math.ceil(box.width),
        windowHeight: Math.ceil(box.height),
        scrollX: 0,
        scrollY: 0,
        useCORS: true,
        logging: false,
        onclone: function(doc){
          // Forzar colores en el DOM clonado (evita casos raros donde el texto queda transparente en el render)
          try{
            var w = doc.getElementById('acal-export-wrap');
            if(!w) return;
            try{
              doc.documentElement.style.margin = '0';
              doc.documentElement.style.padding = '0';
              doc.body.style.margin = '0';
              doc.body.style.padding = '0';
              doc.body.style.background = '#fff';
              // Colocar el contenedor en (0,0) para evitar offsets “corridos”
              w.style.position = 'absolute';
              w.style.left = '0';
              w.style.top = '0';
              w.style.margin = '0';
              w.style.transform = 'none';
            }catch(e){ /* noop */ }
            w.querySelectorAll('.task, .task *').forEach(function(el){
              // eliminar posibles text-fill que html2canvas interpreta mal
              try{ el.style.webkitTextFillColor = ''; }catch(e){}
              el.style.opacity = '1';
              el.style.filter = 'none';
            });
            w.querySelectorAll('.task .t').forEach(function(el){ el.style.color = '#111'; });
            w.querySelectorAll('.task .m').forEach(function(el){ el.style.color = '#6b7280'; });
          }catch(e){ /* noop */ }
        }
      };
      if(extra){ for(var k in extra){ opts[k] = extra[k]; } }
      return html2canvas(wrap, opts);
    }

    function restoreScroll(){
      try{ window.scrollTo(prevX, prevY); }catch(e){}
    }

    function download(canvas){
      var a = document.createElement('a');
      a.href = canvas.toDataURL('image/png');
      a.download = "$fname_js";
      document.body.appendChild(a); a.click(); a.remove();
      restoreScroll();
    }

    // Esperar a que el browser termine de pintar y cargar fuentes (reduce renders “en blanco”)
    var ready = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();
    ready.then(function(){
      requestAnimationFrame(function(){
        requestAnimationFrame(function(){
          // Recalcular tamaño ya con scroll en (0,0) y layout estable
          box = wrap.getBoundingClientRect();

          // Intento 1: foreignObjectRendering suele capturar texto mejor en algunos Chrome.
          doCapture({ foreignObjectRendering: true }).then(download).catch(function(err){
            console.warn('[acal-export] foreignObjectRendering falló, reintentando…', err);
            // Fallback
            doCapture({ foreignObjectRendering: false }).then(download).catch(function(e){
              console.error(e); try{ window.scrollTo(prevX, prevY); }catch(_e){} alert("No se pudo generar el PNG.");
            });
          });
        });
      });
    });
  }
  var btn = document.getElementById("dl");
  if(btn){ btn.addEventListener("click", exportPNG); }
})();
</script>
ACALJS;

    echo '</body></html>';
    exit;
	}


    public function handle_add_tech(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');
        $nombre=$this->sanitize_text($_POST['nombre'] ?? '');
        $color=$this->sanitize_text($_POST['color'] ?? '');
        $activo=isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
        if(!$nombre) wp_die('Nombre requerido');
        $list=$this->get_tecnicos();
        $list[]=['id'=>sanitize_title($nombre.'-'.wp_generate_uuid4()),'nombre'=>$nombre,'color'=>$color?:'#2aa5e8','activo'=>$activo?1:0];
        $this->save_tecnicos($list);
        wp_safe_redirect(admin_url('admin.php?page=acal_tecnicos')); exit;
    }
    public function handle_update_tech(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');
        $id=$this->sanitize_text($_POST['id'] ?? '');
        $nombre=$this->sanitize_text($_POST['nombre'] ?? '');
        $color=$this->sanitize_text($_POST['color'] ?? '');
        $activo=isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
        if(!$id || !$nombre) wp_die('Datos incompletos');
        $list=$this->get_tecnicos();
        foreach($list as &$t){
            if($t['id']===$id){ $t['nombre']=$nombre; $t['color']=$color?:'#2aa5e8'; $t['activo']=$activo?1:0; break; }
        }
        $this->save_tecnicos($list);
        wp_safe_redirect(admin_url('admin.php?page=acal_tecnicos')); exit;
    }
    public function handle_delete_tech(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');
        $id=$this->sanitize_text($_POST['id'] ?? ''); $list=$this->get_tecnicos();
        $list=array_values(array_filter($list, function($t)use($id){ return $t['id']!==$id; }));
        $this->save_tecnicos($list);
        wp_safe_redirect(admin_url('admin.php?page=acal_tecnicos')); exit;
    }

    public function handle_export_data(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');

        $export_format = isset($_POST['export_format']) ? sanitize_key((string)$_POST['export_format']) : 'json';
        $tecnico_id = $this->sanitize_text($_POST['tecnico_id'] ?? '');

        if ($export_format === 'csv'){
            if ($tecnico_id === '') wp_die('Técnico requerido para exportar CSV');

            $tecnicos = $this->get_tecnicos();
            $tecnico_nombre = $tecnico_id;
            foreach ($tecnicos as $t){
                if (($t['id'] ?? '') === $tecnico_id){
                    $tecnico_nombre = (string)($t['nombre'] ?? $tecnico_id);
                    break;
                }
            }

            $q = new WP_Query([
                'post_type'      => self::CPT_TASK,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'   => '_acal_tecnico_id',
                        'value' => $tecnico_id,
                    ],
                ],
                'orderby'        => 'meta_value',
                'meta_key'       => '_acal_fecha',
                'order'          => 'ASC',
            ]);

            $rows = [];
            if ($q->have_posts()){
                while($q->have_posts()){
                    $q->the_post();
                    $meta = get_post_meta(get_the_ID());
                    $rows[] = [
                        $this->sanitize_csv_cell($meta['_acal_fecha'][0] ?? ''),
                        $this->sanitize_csv_cell($tecnico_nombre),
                        $this->sanitize_csv_cell($meta['_acal_cliente'][0] ?? ''),
                        $this->sanitize_csv_cell($meta['_acal_sucursal'][0] ?? ''),
                        $this->sanitize_csv_cell($meta['_acal_equipo'][0] ?? ''),
                        $this->sanitize_csv_cell($meta['_acal_estado'][0] ?? ''),
                        $this->sanitize_csv_cell(strtoupper((string)($meta['_acal_turno'][0] ?? ''))),
                        $this->sanitize_csv_cell($meta['_acal_descripcion'][0] ?? ''),
                    ];
                }
                wp_reset_postdata();
            }

            $slug_tecnico = sanitize_title($tecnico_nombre);
            $filename = 'acal-export-tecnico-'.($slug_tecnico !== '' ? $slug_tecnico : $tecnico_id).'-'.date('Ymd-His').'.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename='.$filename);

            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Fecha', 'Técnico', 'Cliente', 'Sucursal', 'Equipo/Modelo', 'Estado', 'Turno', 'Descripción']);
            foreach ($rows as $row){
                fputcsv($out, $row);
            }
            fclose($out);
            exit;
        }

        $tasks = [];
        $q = new WP_Query([
            'post_type'      => self::CPT_TASK,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ]);

        if ($q->have_posts()){
            while($q->have_posts()){
                $q->the_post();
                $tasks[] = [
                    'post' => [
                        'ID'           => get_the_ID(),
                        'post_title'   => get_the_title(),
                        'post_status'  => get_post_status(),
                        'post_date'    => get_the_date('c'),
                    ],
                    'meta' => get_post_meta(get_the_ID()),
                ];
            }
            wp_reset_postdata();
        }

        $tecnicos = get_option(self::OPT_TECHS, []);
        if (!is_array($tecnicos)) $tecnicos = [];
        $current_user = wp_get_current_user();

        $payload = [
            'version'         => '1.0',
            'exported_at'     => current_time('c'),
            'summary'         => [
                'plugin_version' => self::VERSION,
                'tasks_count'    => count($tasks),
                'tecnicos_count' => count($tecnicos),
                'generated_by'   => ($current_user && !empty($current_user->user_login)) ? $current_user->user_login : '',
            ],
            'tecnicos'        => $tecnicos,
            'tecnicos_order'  => get_option('acal_tecnicos_order', []),
            'tasks'           => $tasks,
        ];

        $filename = 'acal-export-'.date('Ymd-His').'.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$filename);
        echo wp_json_encode($payload);
        exit;
    }

    public function handle_import_data(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');

        if (empty($_FILES['acal_import_file']['tmp_name'])){
            wp_die('Archivo requerido');
        }

        $raw = file_get_contents($_FILES['acal_import_file']['tmp_name']);
        if (!$raw){
            wp_die('No se pudo leer el archivo');
        }

        $data = json_decode($raw, true);
        if (!is_array($data)){
            wp_die('Archivo inválido');
        }

        $replace = !empty($_POST['acal_replace']);
        $allow_duplicates = !empty($_POST['acal_allow_duplicates']);
        $stats = [
            'tasks_total'   => 0,
            'tasks_added'   => 0,
            'tasks_skipped' => 0,
            'tasks_errors'  => 0,
            'tecs_total'    => 0,
            'tecs_added'    => 0,
            'tecs_skipped'  => 0,
            'tasks_skipped_duplicate' => 0,
            'tasks_skipped_invalid'   => 0,
        ];
        if ($replace){
            $q = new WP_Query([
                'post_type'      => self::CPT_TASK,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            if (!empty($q->posts)){
                foreach ($q->posts as $id){
                    wp_delete_post($id, true);
                }
            }
        }

        // Técnicos: si no es reemplazo, agrega solo faltantes por ID.
        if (isset($data['tecnicos']) && is_array($data['tecnicos'])){
            $stats['tecs_total'] = count($data['tecnicos']);
            if ($replace){
                update_option(self::OPT_TECHS, $data['tecnicos'], false);
                $stats['tecs_added'] = count($data['tecnicos']);
            } else {
                $existing_tecs = get_option(self::OPT_TECHS, []);
                if (!is_array($existing_tecs)) $existing_tecs = [];
                $by_id = [];
                foreach ($existing_tecs as $t){
                    if (!empty($t['id'])) $by_id[$t['id']] = $t;
                }
                foreach ($data['tecnicos'] as $t){
                    if (empty($t['id']) || isset($by_id[$t['id']])) { $stats['tecs_skipped']++; continue; }
                    $existing_tecs[] = $t;
                    $stats['tecs_added']++;
                    $by_id[$t['id']] = $t;
                }
                update_option(self::OPT_TECHS, $existing_tecs, false);
            }
        }

        // Orden de técnicos: en merge, preserva existente y agrega IDs nuevos al final.
        if (isset($data['tecnicos_order']) && is_array($data['tecnicos_order'])){
            if ($replace){
                update_option('acal_tecnicos_order', $data['tecnicos_order'], false);
            } else {
                $current_order = get_option('acal_tecnicos_order', []);
                if (!is_array($current_order)) $current_order = [];
                $seen = array_fill_keys($current_order, true);
                foreach ($data['tecnicos_order'] as $id){
                    $id = sanitize_text_field((string) $id);
                    if ($id === '' || isset($seen[$id])) continue;
                    $current_order[] = $id;
                    $seen[$id] = true;
                }
                update_option('acal_tecnicos_order', $current_order, false);
            }
        }

        $tasks = isset($data['tasks']) && is_array($data['tasks']) ? $data['tasks'] : [];
        $stats['tasks_total'] = count($tasks);

        // Si no es reemplazo, indexa tareas existentes para importar solo faltantes.
        $existing_fingerprints = [];
        if (!$replace){
            $existing_q = new WP_Query([
                'post_type'      => self::CPT_TASK,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            if (!empty($existing_q->posts)){
                foreach ($existing_q->posts as $existing_id){
                    $existing_post = get_post($existing_id);
                    if (!$existing_post) continue;
                    $fp = $this->build_task_fingerprint($existing_post->post_title, get_post_meta($existing_id));
                    $existing_fingerprints[$fp] = true;
                }
            }
        }

        $skip_reasons = ['duplicada'=>0,'fecha_invalida'=>0,'meta_invalida'=>0];
        $skip_samples = [];

        foreach ($tasks as $task){
            $post_data = $task['post'] ?? [];
            $meta_data = $task['meta'] ?? [];
            $post_title = $post_data['post_title'] ?? 'Tarea';

            if (!$replace){
                if (!is_array($meta_data)) {
                    $stats['tasks_skipped']++;
                    $stats['tasks_skipped_invalid']++;
                    $skip_reasons['meta_invalida']++;
                    if (count($skip_samples) < 10) $skip_samples[] = ['title'=>$post_title,'reason'=>'meta_invalida','fecha'=>''];
                    continue;
                }

                $fecha_raw = $this->extract_meta_scalar($meta_data, '_acal_fecha');
                if ($fecha_raw !== '' && $this->normalize_date($fecha_raw, false) === '') {
                    $stats['tasks_skipped']++;
                    $stats['tasks_skipped_invalid']++;
                    $skip_reasons['fecha_invalida']++;
                    if (count($skip_samples) < 10) $skip_samples[] = ['title'=>$post_title,'reason'=>'fecha_invalida','fecha'=>$fecha_raw];
                    continue;
                }

                $fp = $this->build_task_fingerprint($post_title, $meta_data);
                if (isset($existing_fingerprints[$fp])) {
                    if (!$allow_duplicates) {
                        $stats['tasks_skipped']++;
                        $stats['tasks_skipped_duplicate']++;
                        $skip_reasons['duplicada']++;
                        if (count($skip_samples) < 10) $skip_samples[] = ['title'=>$post_title,'reason'=>'duplicada','fecha'=>$fecha_raw];
                        continue;
                    }
                }
                $existing_fingerprints[$fp] = true;
            }

            $post_id = wp_insert_post([
                'post_type'   => self::CPT_TASK,
                'post_status' => $post_data['post_status'] ?? 'publish',
                'post_title'  => $post_title,
            ]);
            if (is_wp_error($post_id)) {
                $stats['tasks_errors']++;
                continue;
            }
            $stats['tasks_added']++;

            foreach ($meta_data as $key => $values){
                if (!is_array($values)) { $values = [$values]; }
                foreach ($values as $value){
                    update_post_meta($post_id, sanitize_key($key), maybe_unserialize($value));
                }
            }
        }

        $import_log_key = wp_generate_password(12, false, false);
        set_transient('acal_import_log_'.$import_log_key, [
            'allow_duplicates' => $allow_duplicates ? 1 : 0,
            'reasons' => array_filter($skip_reasons),
            'samples' => $skip_samples,
        ], 10 * MINUTE_IN_SECONDS);

        wp_safe_redirect(add_query_arg([
            'page' => 'acal_import_export',
            'import' => 1,
            'import_log' => $import_log_key,
            'tasks_total' => $stats['tasks_total'],
            'tasks_added' => $stats['tasks_added'],
            'tasks_skipped' => $stats['tasks_skipped'],
            'tasks_errors' => $stats['tasks_errors'],
            'tecs_total' => $stats['tecs_total'],
            'tecs_added' => $stats['tecs_added'],
            'tecs_skipped' => $stats['tecs_skipped'],
        ], admin_url('admin.php')));
        exit;
    }


    public function handle_purge_tasks(){
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_KEY)) wp_die('Nonce inválido');
        if (!$this->can_edit()) wp_die('Permisos insuficientes');

        $deleted = 0;
        $q = new WP_Query([
            'post_type'      => self::CPT_TASK,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        if (!empty($q->posts)){
            foreach ($q->posts as $id){
                if (wp_delete_post($id, true)) {
                    $deleted++;
                }
            }
        }

        wp_safe_redirect(add_query_arg([
            'page'    => 'acal_import_export',
            'purge'   => 1,
            'deleted' => $deleted,
        ], admin_url('admin.php')));
        exit;
    }

    private function render_front_readonly_calendar(array $atts = []){
    // Frontend sin botón "+ Agregar" (solo navegación de semana)
    $atts = shortcode_atts([
        'filters' => '0',
        'legend'  => '0',
        'actions' => '0',   // ignorado en frontend: no mostramos "+ Agregar"
        'lv'      => '1',
        'sticky'  => 'both',
        'refresh' => '0',
    ], $atts, 'calendario_taller');

    $days       = $this->week_range_from_query();
    $renderDays = $atts['lv']==='1' ? array_slice($days,0,5) : $days;
    $tecnicos_raw = array_values(array_filter($this->get_tecnicos(), function($t){
        return !isset($t['activo']) || $t['activo'];
    }));
    $tecnicos = $this->order_tecnicos_array($tecnicos_raw);

    $tasks = $this->get_tasks_for_week($days);

    ob_start();
    $stickyClass = $atts['sticky']==='both' ? 'sticky-both' : ($atts['sticky']==='header' ? 'sticky-header' : '');
    echo '<div class="acal-wrap acal-frontend '.$stickyClass.'" data-refresh="'.esc_attr($atts['refresh']).'">';

    // Topbar SOLO con navegación de semana
    $prev = date('Y-m-d', strtotime($days[0].' -7 days'));
    $next = date('Y-m-d', strtotime($days[0].' +7 days'));
    echo '<form method="get" class="acal-topbar">';
    echo '<div class="acal-nav">';
    echo '<a class="button" href="'.esc_url(add_query_arg(['date'=>$prev])).'">&laquo; Semana anterior</a> ';
    echo '<label>Semana de: <input type="date" name="date" value="'.esc_attr($days[0]).'" /></label> ';
    echo '<button class="button">Ir</button> ';
    echo '<a class="button" href="'.esc_url(add_query_arg(['date'=>$next])).'">Próxima semana &raquo;</a>';
    echo '</div>';
    echo '</form>';

    if ($atts['legend']==='1'){
        echo '<div class="acal-legend">';
        foreach ($tecnicos as $t){
            $fg=$this->best_text_color($t['color']);
            echo '<span class="acal-pill" style="--pill-bg:'.esc_attr($t['color']).';color:'.esc_attr($fg).'">'.esc_html($t['nombre']).'</span>';
        }
        echo '</div>';
    }

    // Grid
    echo '<div class="acal-grid">';
    echo '<div class="acal-cell acal-head acal-tech-col">&nbsp;</div>';
    foreach ($renderDays as $d){
        $label = date_i18n('D d/m', strtotime($d));
        echo '<div class="acal-cell acal-head">'.esc_html(ucfirst($label)).'</div>';
    }

    foreach ($tecnicos as $t){
        $tecId = $t['id'];
        $color = $t['color'];
        $fg    = $this->best_text_color($color);

        echo '<div class="acal-cell acal-tech-col acal-tech-item" data-tecnico-id="'.esc_attr($t['id']).'">';
        echo   '<div class="acal-techname" style="background:'.esc_attr($color).';color:'.esc_attr($fg).'">';
        echo     '<span class="acal-techlabel">'.esc_html($t['nombre']).'</span>';
        echo   '</div>'; // .acal-techname
        echo '</div>';   // .acal-tech-item

        foreach ($renderDays as $d){
            $bg = $this->rgba_from_hex($color, 0.08);
            echo '<div class="acal-cell" style="background:'.esc_attr($bg).'">';
            $cellTasks = $tasks[$tecId][$d] ?? [];

            if (!empty($cellTasks)){
                foreach($cellTasks as $task){
                    $title   = trim($task['descripcion']);
                    $cliente = trim($task['cliente']);
                    $equipo  = trim($task['equipo']);
                    $suc     = $task['sucursal'];
                    $metaLine=[];
                    if($cliente) $metaLine[]=$cliente;
                    if($equipo) $metaLine[]=$equipo;
                    if($suc) $metaLine[]=$suc;
                    $body=implode(' · ',$metaLine);

                    echo '<div class="acal-task" style="border-left:6px solid '.esc_attr($color).'" data-task-id="'.esc_attr($task['id']).'">';
                    echo '<div class="acal-task-title">'.esc_html($title ?: '(Sin descripción)').'</div>';
                    if ($body) echo '<div class="acal-task-meta">'.esc_html($body).'</div>';
                    echo '</div>';
                }
            }
            echo '</div>';
        }
    }
    echo '</div>'; // grid
    echo '</div>'; // wrap
    return ob_get_clean();
}

    public function shortcode_calendar($atts){
        return $this->render_front_readonly_calendar((array) $atts);
    }


    public function maybe_fullscreen(){
        if ($this->is_restricted_context()) { return; }

        if (is_admin() || !is_singular() || !isset($_GET['acal_full']) || $_GET['acal_full']!='1') return;
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'calendario_taller')) return;
        $shortcode='[calendario_taller]';
        if (preg_match('/\[calendario_taller[^\]]*\]/', $post->post_content, $m)){ $shortcode=$m[0]; }
        status_header(200); nocache_headers();
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        wp_head();
        echo '<style>html,body{margin:0;padding:0;background:#fff} .admin-bar .acal-wrap{margin-top:32px} .print-area{display:none!important} .acal-wrap{padding:16px}</style>';
        echo '</head><body class="acal-fullscreen">';
        echo do_shortcode($shortcode);
        wp_footer();
        echo '</body></html>';
        exit;
    }

    public function maybe_front_management(){
        if ($this->is_restricted_context()) { return; }
        if (is_admin() || !$this->is_front_management_request()) return;

        status_header(200);
        nocache_headers();

        if (!is_user_logged_in() || !current_user_can('read')) {
            auth_redirect();
        }

        include plugin_dir_path(__FILE__) . 'templates/frontend-management.php';
        exit;
    }

    public function maybe_standalone(){
        if ($this->is_restricted_context()) { return; }
        if (is_admin() || !$this->is_standalone_request()) return;

        status_header(200);
        nocache_headers();
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        wp_head();
        echo '<style>html,body{margin:0;padding:0;background:#fff} .acal-wrap{padding:16px}</style>';
        echo '</head><body class="acal-standalone">';
        if (!current_user_can('read')) {
            wp_die('No tienes permisos.');
        }
        echo $this->render_front_readonly_calendar([]);
        wp_footer();
        echo '</body></html>';
        exit;
    }

    
}
register_activation_hook(__FILE__, ['ACAL_Calendario_Taller', 'activate_plugin']);
register_deactivation_hook(__FILE__, ['ACAL_Calendario_Taller', 'deactivate_plugin']);
new ACAL_Calendario_Taller();
