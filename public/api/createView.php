<?php

$name = filter_input(INPUT_POST, 'view_name', FILTER_DEFAULT);
$html = filter_input(INPUT_POST, 'html', FILTER_DEFAULT);
$css = filter_input(INPUT_POST, 'css', FILTER_DEFAULT);
$js = filter_input(INPUT_POST, 'javascript', FILTER_DEFAULT);
$midias = filter_input(INPUT_POST, 'midias', FILTER_DEFAULT);
$fonts = filter_input(INPUT_POST, 'fonts', FILTER_DEFAULT);
$param = [
    'title' => filter_input(INPUT_POST, 'titulo_da_pagina', FILTER_DEFAULT) ?? '"{$sitename}"',
    'header' => filter_input(INPUT_POST, 'utilizar_cabecalho', FILTER_VALIDATE_BOOLEAN),
    'navbar' => filter_input(INPUT_POST, 'utilizar_navbar', FILTER_VALIDATE_BOOLEAN),
    'css' => [],
    'js' => [],
    'fonts' => []
];
$cssContent = "";
$jsContent = "";

if (!empty($html) && \Helpers\Check::isJson($html))
    $html = file_get_contents(json_decode($html, !0)[0]['url']);

if (!empty($css) && \Helpers\Check::isJson($css))
    $css = json_decode($css, !0);

if (!empty($js) && \Helpers\Check::isJson($js))
    $js = json_decode($js, !0);

if (!empty($midias) && \Helpers\Check::isJson($midias))
    $midias = json_decode($midias, !0);

if (!empty($fonts) && \Helpers\Check::isJson($fonts))
    $fonts = json_decode($fonts, !0);

\Helpers\Helper::createFolderIfNoExist(PATH_HOME . "public/assets/view");
\Helpers\Helper::createFolderIfNoExist(PATH_HOME . "public/assets/view/" . $name);

/**
 * Cria view file
 */
$f = fopen(PATH_HOME . "public/view/" . $name . ".php", "w+");
fwrite($f, "<div id='core-content-view' class='col'></div>");
fclose($f);

/**
 * Cria CSS file
 */
if (!empty($css)) {

    $mCss = new MatthiasMullie\Minify\CSS("");
    foreach ($css as $c)
        $mCss->add(file_get_contents($c['url']));

    $cssContent = $mCss->minify();
}

/**
 * Cria JS file
 */
if (!empty($js)) {

    $mJs = new MatthiasMullie\Minify\JS("");
    foreach ($js as $c)
        $mJs->add(file_get_contents($c['url']));

    $jsContent = $mJs->minify();
}

/**
 * Cria Midias
 */
if (!empty($midias)) {
    foreach ($midias as $midia) {
        $url = "assets/view/" . $name . "/" . $midia['name'] . "." . $midia['type'];
        copy($midia['url'], PATH_HOME . "public/" . $url);

        /**
         * Atualiza links da mídia em HTML, CSS e JS
         */
        $changes = explode($midia['name'] . "." . $midia['type'], $html);
        if (count($changes) > 1) {
            foreach ($changes as $change) {
                if (!empty($change)) {
                    $link = explode('"', str_replace("'", '"', $change));
                    if(count($link) > 1) {
                        $link = $link[count($link) - 1] . $midia['name'] . "." . $midia['type'];
                        $html = str_replace($link, HOME . $url, $html);
                        $cssContent = str_replace($link, HOME . $url, $cssContent);
                        $jsContent = str_replace($link, HOME . $url, $jsContent);
                    }
                }
            }
        }
    }
}

/**
 * Cria Fonts
 */
if (!empty($fonts)) {
    \Helpers\Helper::createFolderIfNoExist(PATH_HOME . "public/assets/view/" . $name . "/fonts");
    foreach ($fonts as $f) {
        copy($f['url'], PATH_HOME . "public/assets/view/" . $name . "/fonts/" . $f['name'] . $f['type']);

        $param['fonts'][] = HOME . "assets/view/" . $name . "/fonts/" . $f['name'] . $f['type'];
    }
}

/**
 * Cria param file
 */
$f = fopen(PATH_HOME . "public/param/" . $name . ".json", "w+");
fwrite($f, json_encode($param));
fclose($f);


/**
 * Cria tpl file
 */
$f = fopen(PATH_HOME . "public/tpl/view" . ucfirst($name) . ".mustache", "w+");
fwrite($f, $html);
fclose($f);

/**
 * Cria CSS
 */
$f = fopen(PATH_HOME . "public/assets/" . $name . ".css", "w+");
fwrite($f, $cssContent);
fclose($f);

/**
 * Cria JS
 */
$jsContent .= ";$(function(){ $('#core-content-view').htmlTemplate('view" . ucfirst($name) . "', {}); });";
$f = fopen(PATH_HOME . "public/assets/" . $name . ".js", "w+");
fwrite($f, $jsContent);
fclose($f);