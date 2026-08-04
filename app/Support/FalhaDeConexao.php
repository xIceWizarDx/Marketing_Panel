<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;

/**
 * Separa "a internet oscilou" de "este servidor está mal configurado".
 *
 * ⚠️ Os dois chegam como a mesma `ConnectionException`, e tratá-los igual manda
 * a pessoa **tentar de novo para sempre** contra um problema que nunca vai
 * passar sozinho.
 *
 * Foi exatamente o que aconteceu no primeiro teste real: o PHP estava sem o
 * pacote de certificados, então **toda** chamada HTTPS falhava — e a tela dizia
 * "tente de novo em instantes". A causa estava a um `php.ini` de distância, e a
 * mensagem apontava para o lado errado.
 */
class FalhaDeConexao
{
    /**
     * Pedaços que só aparecem em falha de certificado.
     *
     * Comparados em minúsculas porque a mensagem do cURL varia entre versões.
     */
    private const SINAIS_DE_CERTIFICADO = [
        'ssl certificate problem',
        'unable to get local issuer certificate',
        'certificate verify failed',
        'error setting certificate',
        'ssl: certificate subject name',
        'self signed certificate',
    ];

    /**
     * A mensagem certa para o que de fato aconteceu.
     *
     * @param  string  $rede  como a pessoa chama a rede na tela
     */
    public static function explicar(ConnectionException $erro, string $rede): string
    {
        if (! self::ehCertificado($erro)) {
            return "Não conseguimos falar com o {$rede} agora. Tente de novo em instantes.";
        }

        // ⚠️ Fala com quem instalou, não com quem publica: quem lê isso na tela
        // não tem o que fazer sozinho, e "tente de novo" seria mentira.
        return 'Este servidor não consegue validar certificados de segurança, então nenhuma rede '
            ."responde — não é problema da sua conta nem do {$rede}. "
            .'Quem cuida da instalação precisa apontar `curl.cainfo` e `openssl.cafile` '
            .'no `php.ini` para um pacote de certificados (cacert.pem).';
    }

    public static function ehCertificado(ConnectionException $erro): bool
    {
        $mensagem = mb_strtolower($erro->getMessage());

        foreach (self::SINAIS_DE_CERTIFICADO as $sinal) {
            if (str_contains($mensagem, $sinal)) {
                return true;
            }
        }

        return false;
    }
}
