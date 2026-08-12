# Dona Bela Studio — site (`/studio`)

Landing page do studio de beleza, servida em **https://donabelamodascwb.com.br/studio**.

É um site estático independente do site da loja (`website/index.html`) e do sistema PHP.
Tudo (HTML, CSS e JS) está em um único arquivo: `index.html`.

## Como o endereço `/studio` funciona

As rotas foram adicionadas no `vercel.json` (na raiz do projeto):

```json
{ "src": "/studio",      "dest": "/website/studio/index.html" },
{ "src": "/studio/",     "dest": "/website/studio/index.html" },
{ "src": "/studio/(.*)", "dest": "/website/studio/$1" }
```

Ou seja: `/studio/img/hero-salao.jpg` serve `website/studio/img/hero-salao.jpg`.

## Fotos

Basta colocar os arquivos na pasta `img/` com os nomes abaixo — a página passa a
exibi-los automaticamente. Enquanto a foto não existir, aparece um espaço
dourado com ícone no lugar (a página não quebra).

| Arquivo | Onde aparece | Formato sugerido |
| --- | --- | --- |
| `hero-salao.jpg` | Fundo do topo da página | 1920×1080 (paisagem) |
| `sobre-1.jpg` | Sobre nós — unhas | 600×800 (retrato) |
| `sobre-2.jpg` | Sobre nós — cílios | 600×800 (retrato) |
| `sobre-3.jpg` | Sobre nós — cabelo | 600×800 (retrato) |
| `servico-unhas.jpg` | Card de serviço | 800×600 |
| `servico-cilios.jpg` | Card de serviço | 800×600 |
| `servico-cabelo.jpg` | Card de serviço | 800×600 |
| `servico-trancas.jpg` | Card de serviço | 800×600 |
| `espaco-1.jpg` … `espaco-4.jpg` | Galeria "Nosso espaço" | 800×600 |
| `cliente-1.jpg` … `cliente-3.jpg` | Fotos dos depoimentos | 200×200 (quadrada) |
| `insta-1.jpg` … `insta-6.jpg` | Grade do Instagram no rodapé | 300×300 (quadrada) |

Dica: use JPG com no máximo ~300 KB por foto para o site carregar rápido.

## O que revisar antes de publicar

- **Endereço, telefone e e-mail**: hoje usam os mesmos dados da loja
  (Rua Helena Carcereri Piekarski, 579 — Uberaba, Curitiba/PR · (41) 99781-8203).
  Se o studio tiver dados próprios, alterar em `index.html` (rodapé, botões de
  WhatsApp e no bloco `application/ld+json`).
- **Horários**: Seg–Sex 9h–19h, Sáb 9h–17h, Dom fechado (rodapé e `ld+json`).
- **Redes sociais**: os links de Instagram e Facebook no rodapé estão genéricos.
- **Depoimentos**: textos de exemplo — substituir por avaliações reais.

## Testar localmente

```bash
python3 -m http.server 8000 --directory website/studio
# abrir http://localhost:8000
```
