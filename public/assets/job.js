if(typeof showTrabalho === "undefined") {
    function showTrabalho(trabalho) {

        trabalho.penalidade = (typeof trabalho.prazo_maximo !== "undefined" ? parseInt(100 / (trabalho.prazo_maximo - trabalho.prazo_em_dias)) : 20);
        trabalho.isStarted = !isEmpty(trabalho.data_de_inicio);
        trabalho.isInPrazo = (!trabalho.isStarted || moment().diff(moment(trabalho.data_de_inicio.replace("T", " ")).add(trabalho.prazo_em_dias, 'd')) < 0);

        getTemplates().then(templates => {
            //trabalha com os anexos
            if (!isEmpty(trabalho.anexos)) {
                $.each(trabalho.anexos, function (i, e) {
                    trabalho.anexos[i].isImage = e.isImage === "true";
                });

                trabalho.data_de_entrega = moment(trabalho.data_de_entrega).format();
                trabalho.anexos = Mustache.render(templates['job_anexos'], trabalho.anexos);
                console.log(trabalho);
                $("#job").html(Mustache.render(templates.job, trabalho));
            } else {
                $("#job").html(Mustache.render(templates.job, trabalho));
            }
        });
    }

    function readTrabalho() {
        let id = parseInt(history.state.route.replace("job/", ""));
        dbLocal.exeRead("trabalho", id).then(trabalho => {
            if (!isEmpty(trabalho)) {
                showTrabalho(trabalho);
            } else {
                setTimeout(function () {
                    dbLocal.exeRead("trabalho", id).then(trabalho => {
                        if (!isEmpty(trabalho))
                            showTrabalho(trabalho);
                    });
                }, 500);
            }
        });
    }

    function cancelar(id) {
        if(confirm("Deseja recusar este serviço?")) {
            dbLocal.exeRead("trabalho", id).then(data => {
                data.freelance_responsavel = null;
                db.exeCreate("trabalho", data).then(() => {
                    dbLocal.exeDelete("trabalho", id);
                    toast("Serviço foi cancelado", 3000, "toast-success");
                    $("#card-job-" + id).remove();
                });
            });
        }
    }

    function desistir(id) {
        if(confirm("Opa, que pena! Tem certeza que deseja cancelar?")) {
            dbLocal.exeRead("trabalho", id).then(data => {
                data.freelance_responsavel = null;
                data.data_de_inicio = null;
                db.exeCreate("trabalho", data).then(() => {
                    dbLocal.exeDelete("trabalho", id);
                    toast("Você desistiu do serviço", 2500, "toast-warning");
                    setTimeout(function () {
                        pageTransition("dashboard", "route", "forward");
                    },2500);
                });
            });
        }
    }

    function aceitar(id) {
        dbLocal.exeRead("trabalho", id).then(data => {
            data.data_de_inicio = moment().format("YYYY-MM-DD HH:mm:ss");
            db.exeCreate("trabalho", data).then(t => {
                readTrabalho();
                toast("Serviço Aceito! Você tem " + data.prazo_em_dias + " dias para entregar", 6000, "toast-success");
            });
        });
    }

    function finalizar(id) {
        db.exeRead("trabalho", parseInt(id)).then(trab => {
            let entrega = "entrega";
            if(trab.tipo_de_atividade === "1")
                entrega = 'design_page';

            pageTransition(entrega, "form", "forward", "#dashboard", {data: {trabalho: parseInt(id)}});
        });
    }

    function editar(id) {
        db.exeRead("trabalho", parseInt(id)).then(trab => {
            let entrega = "entrega";
            if(trab.tipo_de_atividade === "1")
                entrega = 'design_page';

            console.log(trab);
            console.log(entrega);

            db.exeRead(entrega).then(result => {
                let find = !1;
                if(!isEmpty(result)) {
                    $.each(result, function (i, e) {
                        console.log(e.trabalho);
                        console.log(id);
                        if(e.trabalho == id) {
                            find = !0;
                            pageTransition(entrega, "form", "forward", "#dashboard", {data: {id: parseInt(e.id)}});
                            return !1;
                        }
                    });
                }

                if(!find)
                    pageTransition(entrega, "form", "forward", "#dashboard", {data: {trabalho: parseInt(id)}});
            });
        });
    }
}

$(function () {
    readTrabalho();
});
