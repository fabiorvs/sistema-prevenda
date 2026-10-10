# Sprint 06 — Entregas

## Entregue

- Página **Entregas** com filtro por status e entregas atrasadas.
- Tela por pré-venda com itens comprados, recebidos, pendentes, rastreio, previsão e data real.
- Recebimento parcial por item, com bloqueio para quantidade acima da adquirida.
- Linha do tempo auditável na tabela delivery_events.
- Atualização do status logístico separada de pagamentos e contas a pagar.

## Estados logísticos

reservado, aguardando lançamento, aguardando envio, em transporte,
parcialmente recebido, recebido e cancelado.

O atraso é calculado em tempo de consulta: existe previsão anterior ao dia atual e
o status não está em recebido ou cancelado. Portanto, não altera registros
apenas por passar o tempo.

## Banco de dados

A migration 2026-10-10-000005_AddDeliveryTracking adiciona:

- preorders.tracking_code;
- tabela delivery_events, com status anterior/novo, item, quantidade recebida,
  previsão, data real, rastreio, observação e usuário responsável.

Pré-vendas existentes com o status antigo open são migradas para
awaiting_release. Novas pré-vendas já nascem nesse status.

## Validação manual

1. Abra **Entregas** e entre em uma pré-venda.
2. Registre uma quantidade menor que a comprada: o pedido deve ficar
   parcialmente recebido.
3. Registre o restante: o pedido deve ficar recebido e a data real deve ser gravada.
4. Confirme em **Financeiro** que o saldo e os pagamentos não foram modificados.
5. Tente informar quantidade maior que a pendente: a operação deve ser rejeitada.

## Comandos

    php spark migrate --all
    vendor/bin/phpunit
