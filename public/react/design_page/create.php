<?php

if (empty($dados['freelancer'])) {
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
}