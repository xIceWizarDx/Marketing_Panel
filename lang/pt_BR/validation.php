<?php

/*
|--------------------------------------------------------------------------
| Mensagens de validação
|--------------------------------------------------------------------------
| Sem este arquivo a tela mostra a CHAVE crua ("validation.required") em vez
| da mensagem. Escrito em linguagem de gente: ":attribute" vira o nome do
| campo definido em `attributes`, no fim do arquivo.
*/

return [

    'accepted' => 'É preciso aceitar :attribute.',
    'active_url' => 'O endereço informado em :attribute não é válido.',
    'after' => ':attribute precisa ser uma data depois de :date.',
    'after_or_equal' => ':attribute precisa ser :date ou depois.',
    'alpha' => ':attribute só pode ter letras.',
    'alpha_dash' => ':attribute só pode ter letras, números, hífen e sublinhado.',
    'alpha_num' => ':attribute só pode ter letras e números.',
    'array' => ':attribute precisa ser uma lista.',
    'before' => ':attribute precisa ser uma data antes de :date.',
    'before_or_equal' => ':attribute precisa ser :date ou antes.',
    'boolean' => ':attribute só aceita sim ou não.',
    'confirmed' => 'As duas senhas não são iguais.',
    'current_password' => 'A senha atual não confere.',
    'date' => ':attribute não é uma data válida.',
    'date_equals' => ':attribute precisa ser :date.',
    'date_format' => ':attribute não está no formato esperado.',
    'declined' => ':attribute precisa ser recusado.',
    'different' => ':attribute e :other precisam ser diferentes.',
    'digits' => ':attribute precisa ter :digits dígitos.',
    'digits_between' => ':attribute precisa ter entre :min e :max dígitos.',
    'dimensions' => ':attribute está fora das dimensões aceitas.',
    'distinct' => ':attribute está repetido.',
    'email' => 'Informe um e-mail válido.',
    'ends_with' => ':attribute precisa terminar com um destes: :values.',
    'enum' => 'A opção escolhida em :attribute não é válida.',
    'exists' => 'A opção escolhida em :attribute não existe.',
    'file' => ':attribute precisa ser um arquivo.',
    'filled' => 'Preencha :attribute.',
    'image' => ':attribute precisa ser uma imagem.',
    'in' => 'A opção escolhida em :attribute não é válida.',
    'integer' => ':attribute precisa ser um número inteiro.',
    'ip' => ':attribute precisa ser um endereço de rede válido.',
    'json' => ':attribute precisa estar em formato JSON.',
    'lowercase' => ':attribute precisa estar em minúsculas.',
    'max' => [
        'array' => ':attribute aceita no máximo :max itens.',
        'file' => ':attribute passa do limite de :max KB.',
        'numeric' => ':attribute não pode ser maior que :max.',
        'string' => ':attribute passa de :max caracteres.',
    ],
    'mimes' => ':attribute precisa ser um arquivo do tipo: :values.',
    'mimetypes' => ':attribute precisa ser um arquivo do tipo: :values.',
    'min' => [
        'array' => ':attribute precisa de pelo menos :min itens.',
        'file' => ':attribute precisa ter pelo menos :min KB.',
        'numeric' => ':attribute precisa ser pelo menos :min.',
        'string' => ':attribute precisa ter pelo menos :min caracteres.',
    ],
    'not_in' => 'A opção escolhida em :attribute não é válida.',
    'numeric' => ':attribute precisa ser um número.',
    'present' => ':attribute precisa ser enviado.',
    'prohibited' => ':attribute não pode ser preenchido.',
    'regex' => ':attribute está em um formato que não aceitamos.',
    'required' => 'Preencha :attribute.',
    'required_if' => 'Preencha :attribute quando :other for :value.',
    'required_with' => 'Preencha :attribute quando :values estiver preenchido.',
    'required_without' => 'Preencha :attribute quando :values não estiver preenchido.',
    'same' => ':attribute e :other precisam ser iguais.',
    'size' => [
        'array' => ':attribute precisa ter :size itens.',
        'file' => ':attribute precisa ter :size KB.',
        'numeric' => ':attribute precisa ser :size.',
        'string' => ':attribute precisa ter :size caracteres.',
    ],
    'starts_with' => ':attribute precisa começar com um destes: :values.',
    'string' => ':attribute precisa ser um texto.',
    'timezone' => ':attribute precisa ser um fuso horário válido.',
    'unique' => 'Este :attribute já está em uso.',
    // ⚠️ NÃO diz "tente de novo": a causa mais comum é o arquivo passar do
    // teto do servidor, e repetir vai falhar igual. E sem a contração a
    // frase não vira "de o arquivo".
    'uploaded' => 'Não conseguimos receber :attribute por completo. Costuma ser arquivo grande demais para este servidor, ou conexão interrompida no meio.',
    'uppercase' => ':attribute precisa estar em maiúsculas.',
    'url' => ':attribute precisa ser um endereço válido.',
    'ulid' => ':attribute não é um identificador válido.',
    'uuid' => ':attribute não é um identificador válido.',

    /*
    | Regras de senha (Password::defaults()).
    */
    'password' => [
        'letters' => 'A senha precisa ter pelo menos uma letra.',
        'mixed' => 'A senha precisa ter letra maiúscula e minúscula.',
        'numbers' => 'A senha precisa ter pelo menos um número.',
        'symbols' => 'A senha precisa ter pelo menos um símbolo.',
        // Aparece quando a senha já vazou em algum site — a checagem só roda
        // em produção, e o texto precisa explicar sem assustar.
        'uncompromised' => 'Essa senha já apareceu em vazamentos na internet. Escolha outra.',
    ],

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Nome dos campos
    |--------------------------------------------------------------------------
    | Substitui `:attribute` pelo nome que a pessoa vê na tela. Sem isto, a
    | mensagem sairia "Preencha password" em vez de "Preencha a senha".
    */
    'attributes' => [
        'nome' => 'o nome',
        'email' => 'o e-mail',
        'password' => 'a senha',
        'password_confirmation' => 'a confirmação da senha',
        'current_password' => 'a senha atual',
        'lembrar' => 'continuar conectado',

        'arquivo' => 'o arquivo',
        'tipo' => 'o tipo',
        'midia' => 'a mídia',

        'contas' => 'as contas',
        'titulo' => 'o título',
        'legenda' => 'a legenda',
        'hashtags' => 'as hashtags',

        'identificador' => 'o nome de usuário',
        'senha_de_aplicativo' => 'a senha de aplicativo',

        'fuso_horario' => 'o fuso horário',
        'papel' => 'o papel',
    ],

];
