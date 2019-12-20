function readTrabalhos(repeat) {
    db.exeRead("trabalho").then(trabalhos => {
        if(!isEmpty(trabalhos)) {
            let promessas = [];
            $.each(trabalhos, function (i, e) {
                if(!isEmpty(e.imagem)) {
                    trabalhos[i]['imagem'] = e.imagem[0].urls[300];
                } else {
                    promessas.push(db.exeRead("projeto", parseInt(trabalhos[i].vinculado_ao_projeto)).then(projeto => {
                        if(!isEmpty(projeto) && !isEmpty(projeto.imagem)) {
                            trabalhos[i]['imagem'] = projeto.imagem[0].urls[300];
                        }
                    }));
                }

                trabalhos[i].isStarted = !isEmpty(trabalhos[i].data_de_inicio);
                trabalhos[i].data_de_inicio = moment(trabalhos[i].data_de_inicio).format("lll");
                trabalhos[i].data_de_entrega = moment(trabalhos[i].data_de_entrega).format("lll");
                trabalhos[i].isInPrazo = (!trabalhos[i].isStarted || moment().diff(moment(trabalhos[i].data_de_inicio.replace("T", " ")).add(trabalhos[i].prazo_em_dias, 'd')) < 0 );
            });

            console.log(trabalhos);

            Promise.all(promessas).then(() => {
                $(".dashboard-panel").htmlTemplate('card_job', trabalhos);
            });
        } else {
            if(typeof repeat === "undefined") {
                setTimeout(function () {
                    readTrabalhos(1);
                }, 1);
            }
        }
    });
}

function cancelar(id) {
    if(confirm("Deseja cancelar este serviço?")) {
        dbLocal.exeRead("trabalho", id).then(data => {
            data.freelance_responsavel = null;
            db.exeCreate("trabalho", data).then(t => {
                dbLocal.exeDelete("trabalho", id);
                toast("Serviço foi cancelado", 3000, "toast-success");
                $("#card-job-" + id).remove();
            });
        });
    }
}

$(function () {
    readTrabalhos();
});