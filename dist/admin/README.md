# Painel interno de estatísticas

Área autenticada para acompanhar os dados do Google Analytics 4 do site **psicologafernandavieira.com.br**.

## Acesso

URL: `https://psicologafernandavieira.com.br/admin/`

Usuário inicial:
- **Usuário:** `fernanda`
- **Senha:** `Fernanda@2026`

Troque a senha imediatamente em **Trocar senha**.

## O que o painel mostra

- Visitantes, sessões, visualizações
- Cliques no WhatsApp (evento `whatsapp_click`)
- Tempo médio, rejeição, páginas/sessão
- Horários de pico e dias da semana
- Evolução no tempo
- Páginas, dispositivos, navegadores, SO
- Origem do tráfego, países e cidades

> A lista por IP do exemplo antigo **não é possível** via API oficial do GA4 (política de privacidade do Google).

## Configuração no servidor (Hostinger)

1. Envie a pasta `admin/` junto com o site (HTML/CSS/JS/images).
2. Coloque o JSON da service account em:
   `admin/data/service-account.json`
3. No Google Analytics 4:
   - Admin → Gerenciamento de acesso à propriedade
   - Adicione o e-mail da service account como **Leitor**
   - Ex.: `psicologafernandavieira@graphic-ripsaw-461313-c3.iam.gserviceaccount.com`
4. Ative no Google Cloud (projeto da service account):
   - [Google Analytics Data API](https://console.developers.google.com/apis/api/analyticsdata.googleapis.com/overview?project=903411453192)
   - [Google Analytics Admin API](https://console.developers.google.com/apis/api/analyticsadmin.googleapis.com/overview?project=903411453192)
5. **Property ID (recomendado):** em `admin/includes/config.php`, preencha `property_id` com o número da propriedade.
   - GA4 → Admin (engrenagem) → **Detalhes da propriedade** → **PROPERTY ID** (só números, ex.: `123456789`)
   - Isso evita depender da Admin API para descoberta automática.

## Segurança

- `admin/data/.htaccess` bloqueia acesso HTTP direto aos arquivos de dados.
- Nunca publique `service-account.json` no GitHub.
- Use HTTPS no domínio.

## Desenvolvimento local

```bash
cd admin
php -S localhost:8081
```

Abra `http://localhost:8081/`.
O site público (imagens) precisa estar acessível em `../images` — rode o server na raiz do projeto:

```bash
php -S localhost:8080
```

Painel: `http://localhost:8080/admin/`
