//dijkstra solve graph starting at s
function solve(graph, s) {
    let solutions = {};
    solutions[s] = [];
    solutions[s].dist = 0;

    while (true) {
        let parent = null;
        let nearest = null;
        let dist = Infinity;

        //for each existing solution
        for (let n in solutions) {
            if (!solutions[n])
                continue;
            let ndist = solutions[n].dist;
            let adj = graph[n];
            //for each of its adjacent nodes...
            for (let a in adj) {
                //without a solution already...
                if (solutions[a])
                    continue;
                //choose nearest node with lowest *total* cost
                let d = adj[a] + ndist;
                if (d < dist) {
                    //reference parent
                    parent = solutions[n];
                    nearest = a;
                    dist = d;
                }
            }
        }

        //no more solutions
        if (dist === Infinity)
            break;

        //extend parent's solution path
        solutions[nearest] = parent.concat(nearest);
        //extend parent's cost
        solutions[nearest].dist = dist;
    }


    //Remove as as rotas da solução final,
    //retira a distancia e retira o destino da rota
    let solutionsPoint = {};
    for (let s in solutions) {
        if (!solutions[s] || /^A/.test(s))
            continue;
        solutionsPoint[s] = solutions[s];
        solutionsPoint[s].pop();
    }

    return solutionsPoint;
}

function getDirectionIcon(posicao, destino) {
    const direita = "RIGHT";
    const esquerda = "LEFT";
    const reto = "UP";
    const volte = "DOWN";

    if (posicao === destino)
        return reto;

    switch (posicao) {
        case 'N':
            return (destino === "L" ? direita : (destino === "O" ? esquerda : volte));
        case 'L':
            return (destino === "N" ? esquerda : (destino === "O" ? volte : direita));
        case 'O':
            return (destino === "L" ? volte : (destino === "N" ? direita : esquerda));
        case 'S':
            return (destino === "L" ? esquerda : (destino === "O" ? direita : volte));
    }
}

function compareValues(key, order = 'asc') {
    return function innerSort(a, b) {
        if (!a.hasOwnProperty(key) || !b.hasOwnProperty(key)) {
            // property doesn't exist on either object
            return 0;
        }

        const varA = (typeof a[key] === 'string')
            ? a[key].toUpperCase() : a[key];
        const varB = (typeof b[key] === 'string')
            ? b[key].toUpperCase() : b[key];

        let comparison = 0;
        if (varA > varB) {
            comparison = 1;
        } else if (varA < varB) {
            comparison = -1;
        }
        return (
            (order === 'desc') ? (comparison * -1) : comparison
        );
    };
}

function chartDataOrder(data, order) {
    return data.sort(compareValues(order));
}

//load Distancias
let dist = new Promise((s, f) => {
    $.ajax({
        type: "GET",
        url: HOME + 'unescMapDistancia.json',
        success: function (dist) {
            s(dist);
        },
        error: function (e) {
            f(e);
        },
        dataType: "json",
    });
});

//load Name
let totemName = new Promise((s, f) => {
    $.ajax({
        type: "GET",
        url: HOME + 'unescMapName.json',
        success: function (name) {
            s(name);
        },
        error: function (e) {
            f(e);
        },
        dataType: "json",
    });
});

//load Routes
let routes = new Promise((s, f) => {
    $.ajax({
        type: "GET",
        url: HOME + 'unescMapRoutes.json',
        success: function (routes) {
            s(routes);
        },
        error: function (e) {
            f(e);
        },
        dataType: "json",
    });
});

//load Direction
let directions = new Promise((s, f) => {
    $.ajax({
        type: "GET",
        url: HOME + 'unescMapDirection.json',
        success: function (directions) {
            s(directions);
        },
        error: function (e) {
            f(e);
        },
        dataType: "json",
    });
});

Promise.all([dist, routes, totemName, directions]).then(r => {
    dist = r[0];
    routes = r[1];
    totemName = r[2];
    directions = r[3];

    let totemNamePoint = {1: "Bloco Administrativo", 2: "Biblioteca", 3: "Bloco XXI A", 4: "Bloco S", 5: "Bloco R1"};
    let totemPosition = {1: "P3", 2: "P7", 3: "P14", 4: "P62", 5: "P68"};
    let totemPositionDirection = {"P3": "L", "P7": "O", "P14": "O", "P62": "L", "P68": "L"};
    let exportacao = [];
    let exportacaoAcessibilidade = [];

    // acessibilidade Campus
    // acesse o piso tátil à sua frente,
    // siga em frente por X metros, dobre a direita, siga em frente por X metros, o seu destino esta a sua frente.
    
    for (let i = 1; i < 6; i++) {
        let totem = {
            descricao: totemNamePoint[i],
            id: totemPosition[i],
            destinos: []
        };

        let solutions = solve(routes, totemPosition[i]);

        for (let idDestino in solutions) {

            //cria as rotas
            let rota = {
                descricao: typeof totemName[idDestino] !== "undefined" ? totemName[idDestino] : "NADA",
                orientacoes: [],
                rotas: [],
                id: idDestino,
                dist: solutions[idDestino].dist
            };

            if(rota.descricao !== "Estacionamento" && rota.descricao !== "Totem") {

                delete solutions[idDestino].dist;

                //Dados de rota direction inicial
                let oldRota = totem.id;
                let oldRotaName = totemPositionDirection[oldRota];
                let distancia = 0;
                let icone = "UP";
                let orientacao = {};

                //cria as orientações
                for (let idRota in solutions[idDestino]) {
                    idRota = solutions[idDestino][idRota];
                    icone = getDirectionIcon(oldRotaName, directions[oldRota][idRota]);

                    //se for uma aresta (não ponto)
                    if (typeof dist[idRota] === "number") {

                        //adiciona a rota para as arestas do mapa
                        rota.rotas.push({
                            distancia: dist[idRota] + "m",
                            id: idRota
                        });

                        //sempre que houver uma curva, vamos adicionar uma orientação
                        if (icone !== "UP") {
                            if (!isEmpty(orientacao)) {
                                orientacao.distancia = distancia + "m";
                                rota.orientacoes.push(orientacao);
                            }

                            orientacao = {
                                descricao: (icone === "LEFT" || icone === "RIGHT" ? (isEmpty(rota.orientacoes) ? "Siga pela " : "Vire à ") + (icone === "LEFT" ? "esquerda" : "direita") : "Retorne"),
                                distancia: "",
                                icone: icone
                            };

                            //recomeça o calculo da distância do zero
                            distancia = 0;

                        } else if (distancia === 0 && isEmpty(rota.orientacoes)) {

                            //Primeira orientação seguir reto
                            orientacao = {
                                descricao: "Siga em frente",
                                distancia: "",
                                icone: icone
                            };
                        }

                        //soma a distância até próxima orientação
                        distancia += dist[idRota];
                    }

                    oldRotaName = directions[oldRota][idRota];
                    oldRota = idRota;
                }

                if (distancia > 0) {
                    orientacao.distancia = distancia + "m";
                    rota.orientacoes.push(orientacao);
                    distancia = 0;
                }

                totem.destinos.push(rota);
            }
        }

        totem.destinos = chartDataOrder(totem.destinos, 'descricao');
        totem.destinos.push(totem.destinos[totem.destinos.length - 1]);
        exportacao.push(totem);
    }

    /**
     * Personalização das Orientações Ditadas
     * @param orientacoes
     * @param id
     * @returns {string}
     */
    function geraOrientacoesAcessibilidade(orientacoes, id) {
        let result = "";
        for(let i in orientacoes) {
            let orientacao = orientacoes[i];
            switch (orientacao.descricao) {
                case "Siga em frente":
                case "Siga pela esquerda":
                case "Siga pela direita":
                    result += "Acesse o piso tátil, e " + orientacao.descricao.toLowerCase() + " por " + parseInt(orientacao.distancia) + " metros.";
                    break;
                case "Vire à esquerda":
                case "Vire à direita":
                    result += " " + orientacao.descricao + ", siga por " + parseInt(orientacao.distancia) + " metros.";
                    break;
                case "Retorne":

                    if(id === totemPosition[2] || id === totemPosition[3] || id === totemPosition[5]) {
                        result += "Retorne " + parseInt(orientacao.distancia) + " metros, e acesse o piso tátil.";
                    } else {
                        result += "Retorne " + parseInt(orientacao.distancia) + " metros.";
                    }
                    break;
                default:
                    break;
            }
        }

        result += " Você chegou ao seu destino.";

        return result;
    }

    //Gera Acessibilidade
    for(let i in exportacao) {
        let totem = JSON.parse(JSON.stringify(exportacao[i]));

        for(let e in totem.destinos) {
            totem.destinos[e].orientacoes = geraOrientacoesAcessibilidade(totem.destinos[e].orientacoes, totem.id);
            delete totem.destinos[e].rotas;
        }

        totem.descricao = totem.descricao
            .replace("XXI", "21,")
            .replace("/", ", e")
            .replace("Centac", "centáqui");

        exportacaoAcessibilidade.push(totem);
    }

    //download JSON
    download("MapaTotem.json", JSON.stringify(exportacao));
    download("MapaTotemAcessibilidade.json", JSON.stringify(exportacaoAcessibilidade));

    //show in console
    // console.log(exportacao);
    // console.log(exportacaoAcessibilidade);
});