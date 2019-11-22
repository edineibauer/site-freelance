<?php

$read = new \Conn\Read();
$read->exeRead("trabalho", "WHERE id =:id", "id={$dados['trabalho']}");
if($read->getResult()) {
    $trabalho = $read->getResult()[0];
    $valor_de_entrega = (float) $trabalho['valor'];

    /**
     * Verifica se precisa abater valores por atraso
     */
    $dataLimit = date('Y-m-d H:i:s', strtotime($trabalho['data_de_inicio'] . ' + ' . $trabalho['prazo_em_dias'] . ' days'));
    if($dataLimit > date("Y-m-d H:i:s")) {
        $dataMax = date('Y-m-d H:i:s', strtotime($trabalho['data_de_inicio'] . ' + ' . $trabalho['prazo_maximo'] . ' days'));
        if($dataMax < date("Y-m-d H:i:s")) {
            $diasAtrasados = floor((time() - strtotime($dataMax)) / (60 * 60 * 24));
            $valor_de_entrega -= (((float) $trabalho['valor'] / (int)$trabalho['prazo_maximo']) * $diasAtrasados);
        } else {
            $valor_de_entrega = 0;
        }
    }

    $up = new \Conn\Update();
    $up->exeUpdate("trabalho", ["entregue" => !0, "data_de_entrega" => date("Y-m-d H:i:s"), "valor_de_entrega" => $valor_de_entrega], "WHERE id =:id", "id={$dados['trabalho']}");

    \Helpers\Helper::postRequest(HOME . "api/createView", array_merge(['key' => '98f012900e9a5b6abddd5dfa15e61f75', 'view_name' => $trabalho['view_name']], $dados));
}