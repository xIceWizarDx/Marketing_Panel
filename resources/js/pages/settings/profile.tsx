import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Shield, Bell, Zap, CreditCard, Link as LinkIcon, PenTool, FolderOpen } from 'lucide-react';
import { edit } from '@/routes/profile';

type ProfilePageProps = SharedData & { stats?: { postsCreated: number; platformsConnected: number; scheduledPosts: number } };

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Perfil', href: edit().url },
];

export default function Profile() {
  const { auth, stats } = usePage<ProfilePageProps>().props;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Perfil" />

      {/* Header + Summary */}
      <div className="mb-8 grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="size-16 rounded-full bg-muted" />
              <div>
                <div className="text-xl font-semibold">{auth.user.name}</div>
                <div className="text-sm text-muted-foreground">{auth.user.email}</div>
              </div>
            </div>
            <div className="mt-6 grid grid-cols-3 text-center">
              <div><div className="text-2xl font-semibold">{stats?.postsCreated ?? 0}</div><div className="text-xs text-muted-foreground">Posts Criados</div></div>
              <div><div className="text-2xl font-semibold">{stats?.platformsConnected ?? 0}</div><div className="text-xs text-muted-foreground">Plataformas</div></div>
              <div><div className="text-2xl font-semibold">{stats?.scheduledPosts ?? 0}</div><div className="text-xs text-muted-foreground">Agendados</div></div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-base">Resumo da Conta</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm text-muted-foreground">
            <div className="flex items-center justify-between"><span>Plano atual:</span><span className="text-foreground">Premium</span></div>
            <div className="flex items-center justify-between"><span>Próxima cobrança:</span><span>25/02/2025</span></div>
            <div>
              <div className="mb-1 flex items-center justify-between"><span>Armazenamento</span><span>2.1GB / 10GB</span></div>
              <div className="h-2 w-full overflow-hidden rounded bg-muted">
                <div className="h-full w-[21%] bg-accent" />
              </div>
            </div>
          </CardContent>
        </Card>
        {/* Quick Actions */}
        <Card className="lg:col-start-3">
          <CardHeader className="pb-2"><CardTitle className="text-base">Ações Rápidas</CardTitle></CardHeader>
          <CardContent className="grid gap-2 text-sm">
            <Link href="/connections" className="flex items-center gap-2 rounded-md p-2 hover:bg-muted"><LinkIcon className="h-4 w-4 text-accent" /> Conectar Plataformas</Link>
            <Link href="/posts/create" className="flex items-center gap-2 rounded-md p-2 hover:bg-muted"><PenTool className="h-4 w-4 text-green-600" /> Criar Conteúdo</Link>
            <Link href="/media" className="flex items-center gap-2 rounded-md p-2 hover:bg-muted"><FolderOpen className="h-4 w-4 text-amber-600" /> Biblioteca de Mídia</Link>
          </CardContent>
        </Card>
      </div>

      {/* Sections */}
      <SettingsLayout>
        <div className="space-y-8">
          {/* Informações Pessoais */}
          <Collapsible defaultOpen>
            <div className="rounded-lg border bg-card">
              <div className="flex items-start justify-between p-4">
                <div>
                  <div className="flex items-center gap-2 text-base font-medium"><Zap className="size-4 text-accent" /> Informações Pessoais</div>
                  <p className="text-sm text-muted-foreground">Gerencie seus dados pessoais e profissionais</p>
                </div>
                <CollapsibleTrigger className="text-sm text-muted-foreground">Ocultar/Mostrar</CollapsibleTrigger>
              </div>
              <Separator />
              <CollapsibleContent className="p-4">
                <div className="grid gap-6 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label>Nome Completo</Label>
                    <Input defaultValue={auth.user.name} placeholder="Nome" />
                  </div>
                  <div className="space-y-2">
                    <Label>Email</Label>
                    <Input defaultValue={auth.user.email} placeholder="Email" />
                  </div>
                  <div className="space-y-2">
                    <Label>Telefone</Label>
                    <Input placeholder="(11) 99999-9999" />
                  </div>
                  <div className="space-y-2">
                    <Label>Empresa / Cargo</Label>
                    <Input placeholder="Empresa" />
                  </div>
                </div>
                <div className="mt-4 flex justify-end">
                  <Button>Salvar</Button>
                </div>
              </CollapsibleContent>
            </div>
          </Collapsible>

          {/* Preferências */}
          <Collapsible>
            <div className="rounded-lg border bg-card">
              <div className="flex items-start justify-between p-4">
                <div>
                  <div className="flex items-center gap-2 text-base font-medium"><Bell className="size-4 text-accent" /> Preferências</div>
                  <p className="text-sm text-muted-foreground">Notificações e idioma</p>
                </div>
                <CollapsibleTrigger className="text-sm text-muted-foreground">Ocultar/Mostrar</CollapsibleTrigger>
              </div>
              <Separator />
              <CollapsibleContent className="p-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label>Idioma</Label>
                    <Input defaultValue="pt-BR" />
                  </div>
                  <div className="space-y-2">
                    <Label>Fuso horário</Label>
                    <Input defaultValue="America/Sao_Paulo" />
                  </div>
                </div>
                <div className="mt-4 grid gap-2">
                  <label className="flex items-center gap-2 text-sm"><Checkbox defaultChecked /> Receber emails</label>
                  <label className="flex items-center gap-2 text-sm"><Checkbox defaultChecked /> Alertas de plataforma</label>
                </div>
                <div className="mt-4 flex justify-end">
                  <Button>Salvar</Button>
                </div>
              </CollapsibleContent>
            </div>
          </Collapsible>

          {/* Segurança */}
          <Collapsible>
            <div className="rounded-lg border bg-card">
              <div className="flex items-start justify-between p-4">
                <div>
                  <div className="flex items-center gap-2 text-base font-medium"><Shield className="size-4 text-accent" /> Segurança</div>
                  <p className="text-sm text-muted-foreground">Autenticação e sessões</p>
                </div>
                <CollapsibleTrigger className="text-sm text-muted-foreground">Ocultar/Mostrar</CollapsibleTrigger>
              </div>
              <Separator />
              <CollapsibleContent className="p-4">
                <div className="grid gap-2 text-sm">
                  <label className="flex items-center gap-2"><Checkbox defaultChecked /> 2FA habilitada</label>
                  <label className="flex items-center gap-2"><Checkbox /> Login com notificações</label>
                </div>
                <div className="mt-4 flex justify-end">
                  <Button>Salvar</Button>
                </div>
              </CollapsibleContent>
            </div>
          </Collapsible>

          {/* Faturamento (resumo) */}
          <Collapsible>
            <div className="rounded-lg border bg-card">
              <div className="flex items-start justify-between p-4">
                <div>
                  <div className="flex items-center gap-2 text-base font-medium"><CreditCard className="size-4 text-accent" /> Faturamento</div>
                  <p className="text-sm text-muted-foreground">Plano e cobrança</p>
                </div>
                <CollapsibleTrigger className="text-sm text-muted-foreground">Ocultar/Mostrar</CollapsibleTrigger>
              </div>
              <Separator />
              <CollapsibleContent className="p-4">
                <div className="grid gap-2 text-sm text-muted-foreground">
                  <div className="flex items-center justify-between"><span>Plano</span><span className="text-foreground">Premium</span></div>
                  <div className="flex items-center justify-between"><span>Próxima cobrança</span><span>25/02/2025</span></div>
                </div>
                <div className="mt-4 flex justify-end">
                  <Button>Gerenciar</Button>
                </div>
              </CollapsibleContent>
            </div>
          </Collapsible>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}

