<?php

if (!$user->isLoggedIn()) {
  Redirect::to(URL::build('/login'));
  die();
}

if (!isset($_GET['res_id'])) {
  Redirect::to(URL::build('/resources'));
  die();
}

define('PAGE', 'resources');
define('RESOURCE_PAGE', 'stripe_resource');
$page_title = $resource_language->get('resources', 'resources') . ' - ' . str_replace('{x}', $p, $language->get('general', 'page_x'));

require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$resource = DB::getInstance()->get('resources', ['id', '=', $_GET['res_id']])->first();



$cache->setCache('stripe_user_data');

if (!$cache->isCached('stripe_pub_' . $resource->creator_id) or empty($cache->retrieve('stripe_pub_' . $resource->creator_id))) {
  Redirect::to(URL::build('/resources/resource/' . $resource->id));
  die();
} else {
  $publishable_key = $cache->retrieve('stripe_pub_' . $resource->creator_id);
}

// Get currency
$currency = DB::getInstance()->get('settings', ['name', '=', 'resources_currency'])->first();
$currency = $currency->value;


$smarty->assign(array(
  'USER' => $user->data(),
  'RESOURCE' => $resource,
  'CURRENCY' => $currency,
  'PROCESS_URL' => URL::build('/resource/stripe/process'),
  'CARD_HOLDER_NAME' => $res_gateway_language->get('general', 'card_holder_name'),
  'EMAIL' => $res_gateway_language->get('general', 'email'),
  'CARD_NUMBER' => $res_gateway_language->get('general', 'card_number'),
  'MONTH' => $res_gateway_language->get('general', 'month'),
  'YEAR' => $res_gateway_language->get('general', 'year'),
  'CVV' => $res_gateway_language->get('general', 'cvv'),
  'PAY_NOW' => $res_gateway_language->get('general', 'pay_now'),
  'PUBLISHABLE_KEY' => $publishable_key,
  'TOKEN' => Token::get(),
));


$template_file = 'resources-gateway/stripe_form.tpl';

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

$smarty->assign('WIDGETS', $widgets->getWidgets());

if (Session::exists('stripe_success'))
  $success = Session::flash('stripe_success');

if (Session::exists('stripe_error'))
  $errors = Session::flash('stripe_error');

if (isset($success))
  $smarty->assign(array(
    'SUCCESS' => $success,

  ));

if (isset($errors) && count($errors))
  $smarty->assign(array(
    'ERROR' => $errors,
  ));

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate($template_file, $smarty);
