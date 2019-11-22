<?php

if (empty($dados['freelance_responsavel'])) {
    $dados['tipo_de_atividade'];

    $where = "WHERE status = 1 AND trabalhos_pendentes < 30";

    if ($dados['urgente'])
        $where .= " AND nivel > 2 AND pontualidade >= 5";

    $lista = [];
    $read = new \Conn\Read();
    $read->exeRead("freelancer", $where . " ORDER BY privilegio DESC LIMIT 500");
    if ($read->getResult()) {
        foreach ($read->getResult() as $freelance) {

            //se este freelance for da área
            if (in_array($dados['tipo_de_atividade'], json_decode($freelance['areas_de_atuacao'], !0))) {
                $lista[$freelance['id']] = $freelance['nivel'] + $freelance['qualidade'] + $freelance['pontualidade'];

                //quanto maior o privilégio, maior o número de projetos pendentes que ele consegue suportar sem baixar a nota
                $lista[$freelance['id']] += $freelance['privilegio'] - $freelance['trabalhos_pendentes'];
            }
        }
        arsort($lista);
        $autor = array_keys($lista)[0];

        $up = new \Conn\Update();
        $up->exeUpdate("trabalho", ["freelance_responsavel" => $autor], "WHERE id = :id", "id={$dados['id']}");
        \Dashboard\Note::create("Você recebeu uma tarefa!", $dados['nome_da_atividade'], $autor);
    }
}
