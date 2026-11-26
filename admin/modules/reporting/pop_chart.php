<?php
/**
 * @Created by          : Heru Subekti (heroe.soebekti@yahoo.co.id)
 * @Date                : 2020-01-23 08:01
 * @File name           : pop_chart.php
 */


// key to authenticate
define('INDEX_AUTH', '1');

// load SLiMS main system configuration
require '../../../sysconfig.inc.php';

// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

$array_color = array('#e6194b', '#3cb44b', '#ffe119', '#4363d8', '#f58231', '#911eb4', '#46f0f0', '#f032e6', '#bcf60c', '#fabebe', '#008080', '#e6beff', '#9a6324', '#fffac8', '#800000', '#aaffc3', '#808000', '#ffd8b1', '#000075', '#808080', '#ffffff', '#000000');

ob_start();

$label = array();
$data = array();

$filter = simbio_security::xssFree($_GET['filter']??'');
$chart = $_SESSION['chart']??[];
$page_title = isset($chart['title']) ? $chart['title'].$filter : 'Chart ' . $filter;
// Chart.js 2: jenis grafik huruf kecil; hanya 'bar' atau 'line' yang diizinkan
$chart_type = strtolower($chart['chart_type'] ?? 'line') === 'bar' ? 'bar' : 'line';

foreach ($chart['xAxis']??[] as $key => $value) {
	array_push($label,$value);
}

$num_color = 1;
$legend = [];

$dataset = !empty($filter) && isset($chart['data']) ? ($chart['data'][$filter] ?? []) : ($chart['data'] ?? []);

foreach ($dataset as $key => $value) {
	$color = $array_color[$num_color];
    $legend[$key] = $color;
	$data[] = array(
		'label' => __(ucfirst(str_replace('_', ' ', $key))),
		'backgroundColor' => ($chart_type === 'line' ? 'rgba(220,220,220,0)' : $color),
		'borderColor' => $color,
		'pointBackgroundColor' => $color,
		'fill' => $chart_type !== 'line',
		'lineTension' => 0, // garis lurus (dulu: bezierCurve false)
		'data' => array_values($value));
    $num_color++;
}

?>
<script src="<?php echo JWB?>chartjs/chart-2.9.4.min.js"></script>
<h5 style="text-align: center;"><?= $page_title; ?></h5>
<hr/><br/>
<div class="s-chart">                        
    <canvas id="chartjs"></canvas>   
    <div class="s-dashboard-legend">
    <?php
    foreach ($legend as $labels => $color) {
        echo '<i class="fa fa-square" style="color:'.$color.';"></i>&nbsp;'.__(ucfirst(str_replace('_', ' ',$labels))).'&nbsp;';
    }
    ?>
    </div>           
</div>

<script>
$(function(){  
    // Chart.js 2.9.4 (Chart.js 1.x lama punya celah CVE-2020-7746)
    new Chart(document.getElementById('chartjs'), {
        type: <?php echo json_encode($chart_type) ?>,
        data: {
            labels: <?php echo json_encode($label, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            datasets: <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            legend: { display: false }, // legenda sudah ada di bawah grafik (HTML)
            scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }
        }
    });
});    

</script>

<?php
/* main content end */
$content = ob_get_clean();
// include the page template
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';



