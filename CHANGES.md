# CHANGES.md


BUG 1 — EnsureCampaignIsDraft: condição invertida

app/Http/Middleware/EnsureCampaignIsDraft.php

A condição bloqueava campanhas em draft e deixava passar campanhas já em sending ou sent exatamente o oposto do pretendido.

Era impossível enviar uma campanha nova. Campanhas já enviadas podiam ser re-enviadas, duplicando todos os envios.

if (! $campaign->isDraft()) em vez de if ($campaign->status === 'draft').

BUG 2 — Migration campaigns: scheduled_at como string

database/migrations/2024_01_01_000003_create_campaigns_table.php

O campo estava definido como $table->string('scheduled_at').

Comparações de data no scheduler (<=) e ordenações funcionavam por acidente com strings no formato ISO, mas falhariam com qualquer outro formato ou timezone.

$table->timestamp('scheduled_at')->nullable()->index().


BUG 3 — Migration contacts: email sem unique

database/migrations/0001_01_01_000003_create_contacts_table.php

Sem unique na base de dados, dois pedidos concorrentes podiam criar o mesmo email em simultâneo, passando pela validação do FormRequest.

O mesmo contacto recebia campanhas em duplicado e o unsubscribe só afectava uma das linhas.

$table->string('email')->unique().

Migration campaign_sends: sem unique em (campaign_id, contact_id)

database/migrations/2024_01_01_000004_create_campaign_sends_table.php

Sem este constraint, o mesmo contacto podia ter múltiplas linhas para a mesma campanha.

Um dispatch duplicado enviava o email várias vezes ao mesmo contacto e enchia a queue com jobs redundantes.

$table->unique(['campaign_id', 'contact_id'])$table->index('status') para as queries de agregação de stats.

BUG 5 — Migration contact_contact_list: sem unique

database/migrations/2024_01_01_000002_create_contact_contact_list_table.php

Um contacto podia ser adicionado à mesma lista mais do que uma vez.

Cada linha duplicada resultava num email extra por campanha.

$table->unique(['contact_id', 'contact_list_id']).

BUG 6 — Campaign::getStatsAttribute: stats calculadas em PHP em vez de SQL

app/Models/Campaign.php

Problema: O accessor carregava toda a collection de sends em memória ($this->sends) e contava em PHP.

Numa campanha muitos contactos, isto hidrata vários modelos Eloquent em memória apenas para contar três grupos, o que seria um problema de memória garantido em produção.

Substituído por uma query GROUP BY status com COUNT(*), que devolve apenas 3 linhas independentemente do volume. O CampaignRepository usa withCount() para as listagens, resolvendo tudo numa única query.

BUG 7 — CampaignService::dispatch(): sem chunking, sem idempotência, race condition

app/Services/CampaignService.php

->get() carregava todos os contactos de uma vez com listas grandes.
CampaignSend::create() inseria sempre, sem verificar se já existia, logo emails duplicados.
O status era actualizado para sending depois do loop — o scheduler podia re-enviar a campanha enquanto o loop ainda corria.

Status atualizado antes do loop; chunkById(200) para processar em lotes; firstOrCreate() para idempotência; jobs só despachados quando $send->wasRecentlyCreated. 
Removidos também buildPayload() e resolveReplyTo(), que referenciavam $campaign->reply_to, uma coluna que não existe.

BUG 8 -SendCampaignEmail job: sem retry, sem idempotência, N+1

app/Jobs/SendCampaignEmail.php

$send->contact->email e $send->campaign->subject eram carregados por uma query extra por acesso.
Sem $tries, jobs falhados retentavam indefinidamente, bloqueando a queue.
Se o worker crashasse após enviar o email mas antes de marcar como sent, o job era re-entregue e enviava o email de novo.

$tries = 3, $backoff = [30, 60, 120], $timeout = 60; eager load com with(['contact', 'campaign']); lockForUpdate() dentro de transacção para garantir que apenas um worker processa cada send, e where('status', 'pending').

BUG 9 — Scheduler: re-enviava campanhas já enviadas, sem chunking

app/Console/Kernel.php

Sem filtro de status, campanhas em sending ou sent eram re-enviadas a cada minuto.
->get() carregava todas as campanhas em memória de uma vez.

Adicionado filtro where('status', 'draft'), chunkById(50) e ->withoutOverlapping() para evitar execuções concorrentes do scheduler.


Adições de arquitectura

Criadas interfaces  com implementações Eloquent correspondentes, ligadas via RepositoryServiceProvider. Os controllers dependem apenas das interfaces.

Models em falta:Contact, ContactList e CampaignSen` não existiam no repositório. 

API: Implementados todos os endpoints especificados com FormRequest