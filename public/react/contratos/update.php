<?php
if($dados['aprovado']) {
    $read = new \Conn\Read();
    $read->exeRead("projeto", "WHERE proposta = :pp", "pp={$dados['proposta']}");
    if(!$read->getResult()) {
        $proposta = \Entity\Entity::read("propostas", ['id' => (int) $dados['proposta']]);
        if(!empty($proposta)) {
            foreach ($proposta['etapas'] as $i => $p)
                $proposta['etapas'][$i]['columnName'] = "escopo";
        }

        $projetoName = str_replace("_", "", \Helpers\Check::name($proposta['nome_do_projeto']));

        $create = new \Conn\Create();
        $create->exeCreate("projeto", [
            "proposta" => $dados['proposta'],
            "escopo" => json_encode($proposta['etapas']),
            "url" => $projetoName . ".ag3tecnologia.com.br"
        ]);

        if($create->getResult()) {

            /**
             * Se estiver no servidor (se estiver usando SSL)
             */
            if(SSL)
                include_once 'createProjectOnServer.php';

            $read->exeRead("usuario_backend");
            if($read->getResult()) {
                foreach ($read->getResult() as $backend) {
                    $note = new \Dashboard\Notification();
                    $note->setTitulo("Projeto " . $proposta['nome_do_projeto'] . " Pronto!");
                    $note->setDescricao($backend['nome'] . ", prepare o escopo do projeto.");
                    $note->setUrl(HOME . "dashboard");
                    $note->setImagem(!empty($autor['imagem']) ? $autor['imagem'][0]['urls'][300] : "");
                    $note->setUsuario((int) $backend['usuarios_id']);
                    $note->enviar();
                }
            }
        }
    }
}