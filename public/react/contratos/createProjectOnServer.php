<?php

$sub = $projetoName;
$emailCloud = "galera.org@gmail.com";
$domainCloud = "ag3tecnologia.com.br";
$zoneCloud = "6c7986793fd359a5e855456effb75620";
$keyCloud = "9ae60ac88d101da0e4667dde831bcd3a96d46";
$mysqlServername = "localhost";
$mysqlUsername = "freelancer";
$mysqlPassword = "GOEk,q$@-nA?1ytL";
$dirDefaultConf = "/var/www/freelancer/000-default.conf";
$dirDefaultComposer = "/var/www/freelancer/000-default.json";
$content = "";

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 16; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

$senha = randomPassword();

/**
 * Cria o DNS
 */
shell_exec('curl -X POST "https://api.cloudflare.com/client/v4/zones/' . $zoneCloud . '/dns_records" -H "X-Auth-Email: ' . $emailCloud . '" -H "X-Auth-Key: ' . $keyCloud . '" -H "Content-Type: application/json" --data \'{"type":"CNAME","name":"' . $sub . '","content":"' . $domainCloud . '","ttl":1,"priority":10,"proxied":false}\'');

/**
 * Cria o diretório
 */
shell_exec("mkdir /var/www/{$sub} && mkdir /var/www/{$sub}/public_html");

/**
 * Cria tmp Conf vhost para poder mover via shell para a pasta correta
 * Ativa o subdomínio e aponta para o diretório e depois remove tmp Conf vhost
 */
$f = fopen("{$sub}.ag3tecnologia.com.conf", "w");
fwrite($f, str_replace("{{sub}}", $sub, file_get_contents($dirDefaultConf)));
fclose($f);
shell_exec("mv {$sub}.ag3tecnologia.com.conf /etc/apache2/sites-available/{$sub}.ag3tecnologia.com.conf");
shell_exec("a2ensite {$sub}.ag3tecnologia.com.conf");

/**
 * Salva a senha do banco recem criada
 */
$f = fopen("senhaBanco.txt", "w");
fwrite($f, $senha);
fclose($f);
shell_exec("mv senhaBanco.txt /var/www/{$sub}/senhaBanco.txt");

/**
 * Cria o banco, usuário e seta permissão
 */
$conn = new mysqli($mysqlServername, $mysqlUsername, $mysqlPassword);
if (!$conn->connect_error) {
    $conn->query("CREATE USER '{$sub}'@'localhost' IDENTIFIED BY '{$senha}'");
    $conn->query("CREATE DATABASE {$sub}");
    $conn->query("GRANT ALL ON {$sub}.* TO '{$sub}'@'localhost'");
    $conn->query("FLUSH PRIVILEGES");
} else {
    die("Connection failed: " . $conn->connect_error);
}
$conn->close();

/**
 * Cria o SSL
 */
echo shell_exec("certbot --apache -n -d {$sub}.ag3tecnologia.com.br");

/**
 * Cria composer json com require ueb/dashboard
 */
shell_exec("cp -r {$dirDefaultComposer} /var/www/{$sub}/public_html/composer.json");

/**
 * Cria crontab com composer update para $sub
 */
$newCron = "cd /var/www/{$sub}/public_html && /usr/local/bin/composer update --no-plugins --no-scripts && \n" . file_get_contents("/var/www/freelancer/cron.sh");
$f = fopen("tpmNewCron.txt", "w");
fwrite($f, $newCron);
fclose($f);
shell_exec("mv tpmNewCron.txt /var/www/freelancer/cron.sh && cd /var/www/freelancer && chmod +x cron.sh");

/**
 * Da permissão na pasta
 */
echo shell_exec("cd /var/www/{$sub} && chown www-data:www-data public_html -R && systemctl restart apache2");