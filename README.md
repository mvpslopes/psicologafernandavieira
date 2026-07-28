# Site — Fernanda Vieira Psicologia

Site institucional em **HTML, CSS e JavaScript puros** (sem frameworks), pronto para ser hospedado na **Hostinger**.

## Estrutura de pastas

```
├── index.html          → Home
├── sobre.html           → Sobre
├── metodologia.html     → Metodologia de Trabalho
├── beneficios.html      → Benefícios da Terapia
├── faq.html              → Perguntas Frequentes
├── contato.html          → Contato
├── css/
│   └── style.css         → Todo o estilo visual do site
├── js/
│   └── main.js            → Menu mobile, accordion do FAQ, animações, formulário
├── images/               → Fotos e logos já otimizados para a web
├── robots.txt
└── sitemap.xml
```

As pastas `/fotos` e `/logo` contêm os arquivos originais enviados por você — **não precisam ser enviadas para a Hostinger**, servem apenas de backup/arquivo-fonte.

## Como publicar na Hostinger

1. Acesse o **hPanel** da Hostinger e entre em **Gerenciador de Arquivos** (ou use um cliente FTP como o FileZilla).
2. Abra a pasta `public_html` do domínio `psicologafernandavieira.com.br`.
3. Envie **todo o conteúdo** desta pasta do projeto (os arquivos `.html`, e as pastas `css`, `js`, `images`, além de `robots.txt` e `sitemap.xml`) para dentro de `public_html`.
   - Não envie as pastas `fotos` e `logo` (são os arquivos originais, mais pesados e não utilizados pelo site).
4. Acesse `https://psicologafernandavieira.com.br` para conferir se tudo carregou corretamente.
5. (Opcional) Ative o certificado SSL gratuito no hPanel (Hostinger já oferece isso na maioria dos planos) para o site abrir com o cadeado `https://`.

## O que você pode querer atualizar

### WhatsApp e e-mail
O número de WhatsApp `31988671693` e o e-mail `fernandacarolinev53@gmail.com` aparecem em vários pontos (cabeçalho, rodapé, botão flutuante, formulário de contato). Caso troque algum dado, procure e substitua em todos os arquivos `.html` (ou peça para quem for atualizar o site futuramente).

### Depoimentos (Home)
Na seção **"O que dizem quem já caminhou comigo"**, dentro do `index.html`, os três depoimentos atuais são **ilustrativos (mockup)**, respeitando o sigilo profissional conforme combinado. Há um comentário no código (`<!-- NOTA PARA A FERNANDA ... -->`) indicando exatamente onde substituir pelo texto real, a inicial do nome e a "tag" (ex.: "Paciente em acompanhamento"), assim que reunir as autorizações necessárias.

### Textos e fotos
Todos os textos vieram do briefing e podem ser ajustados livremente abrindo o arquivo `.html` correspondente em qualquer editor de texto. As fotos usadas estão em `images/` com nomes descritivos (ex.: `sobre-perfil.jpg`, `beneficios-hero.jpg`) — para trocar uma foto, basta substituir o arquivo mantendo o mesmo nome, ou trocar o nome referenciado na tag `<img src="...">`.

## Identidade visual usada

- **Cores:** verde-sálvia `#94a281` e rosa `#fde9e1`, com tons neutros em creme e um rosa-terracota de apoio, extraídos da sua logo.
- **Tipografia:** `Fraunces` para títulos (estilo amigável/orgânico) e `Lora` para textos (elegante e legível), carregadas via Google Fonts.
- **Estilo:** formas orgânicas (blobs), cantos suaves e bastante espaço em branco, sem poluição visual — inspirado nas referências enviadas.

## Funcionalidades incluídas

- Menu responsivo (mobile com menu lateral).
- Botão flutuante de WhatsApp em todas as páginas.
- Formulário de contato que monta a mensagem e abre o WhatsApp automaticamente (não precisa de servidor/backend).
- Perguntas Frequentes em formato acordeão (clique para abrir/fechar).
- Dados estruturados (Schema.org FAQPage) na página de perguntas frequentes, para ajudar no SEO/Google.
- `robots.txt` e `sitemap.xml` prontos para facilitar a indexação no Google.

Qualquer dúvida na hora de publicar, é só chamar!
