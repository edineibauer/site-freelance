<?php

if($dados['aprovada']) {
    $read = new \Conn\Read();
    $read->exeRead("contratos", "WHERE proposta = :pp", "pp={$dados['id']}");
    if(!$read->getResult()) {
        $create = new \Conn\Create();
        $create->exeCreate("contratos", [
            "proposta" => $dados['id'],
            "observacoes" => $dados['observacoes']
        ]);

        if($create->getResult()) {
            $read->exeRead("usuario_gerente");
            if($read->getResult()) {
                $autor = \Entity\Entity::read("usuarios", ['id' => (int) $dados['autorpub']]);
                foreach ($read->getResult() as $gerentes) {
                    $note = new \Dashboard\Notification();
                    $note->setTitulo("Proposta " . $dados['nome_do_projeto'] . " Aceita!");
                    $note->setDescricao("Prepare o contrato " . $gerentes['nome'] . ". " . $autor['nome'] . " fechou a proposta com o cliente.");
                    $note->setUrl(HOME . "dashboard");
                    $note->setImagem($autor['imagem'][0]['urls'][300]);
                    $note->setUsuario((int) $autor['id']);
                    $note->enviar();
                }
            }
        }
    }
}