<?php

$read = new \Conn\Read();
$read->exeRead("trabalho", "WHERE id =:id", "id={$dados['trabalho']}");
if($read->getResult()) {
    $trabalho = $read->getResult()[0];
    $valor_de_entrega = (float) $trabalho['valor'];

    /**
     * Cria cópia de verificação
     */
    \Helpers\Helper::createFolderIfNoExist(PATH_HOME . "_cdn/entregas");
    $f = fopen(PATH_HOME . "_cdn/entregas/{$trabalho['view_name']}.json", "w+");
    fwrite($f, json_encode($dados));
    fclose($f);

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
}

/*if (empty($dados['freelancer'])) {
    $meta = \Entity\Metadados::getDicionario("freelancer");
    foreach ($meta[2]['allow']['options'] as $option) {
        if($option['representacao'] === "Front-end") {

            $lista = [];
            $read = new \Conn\Read();
            $read->exeRead("freelancer", "WHERE status = 1 AND trabalhos_pendentes < 30 ORDER BY privilegio DESC LIMIT 500");
            if ($read->getResult()) {
                foreach ($read->getResult() as $freelance) {
                    //se este freelance for da área
                    if (in_array($option['valor'], json_decode($freelance['areas_de_atuacao'], !0))) {
                        $lista[$freelance['id']] = $freelance['nivel'] + $freelance['qualidade'] + $freelance['pontualidade'];

                        //quanto maior o privilégio, maior o número de projetos pendentes que ele consegue suportar sem baixar a nota
                        $lista[$freelance['id']] += $freelance['privilegio'] - $freelance['trabalhos_pendentes'];
                    }
                }

                if(!empty($lista)) {
                    arsort($lista);
                    $autor = array_keys($lista)[0];

                    $up = new \Conn\Update();
                    $up->exeUpdate("design_page", ["freelancer" => $autor], "WHERE id = :id", "id={$dados['id']}");
                    \Dashboard\Note::create("Uma nova tela para você desenvolver!", \Helpers\Check::words($dados['descricao']), $autor);
                }
            }
            break;
        }
    }
}*/