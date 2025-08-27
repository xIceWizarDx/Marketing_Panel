import React, { useState } from 'react';
import GlobalHeader from '../../components/ui/GlobalHeader';
import NavigationDrawer from '../../components/ui/NavigationDrawer';
import BreadcrumbNavigation from '../../components/ui/BreadcrumbNavigation';
import { ToastProvider, useToast } from '../../components/ui/ToastNotificationSystem';
import ProfileHeader from './components/ProfileHeader';
import PersonalInfoSection from './components/PersonalInfoSection';
import PreferencesSection from './components/PreferencesSection';
import SecuritySection from './components/SecuritySection';
import BillingSection from './components/BillingSection';
import Icon from '../../components/AppIcon';


const UserProfileContent = () => {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [expandedSections, setExpandedSections] = useState({
    personal: true,
    preferences: false,
    security: false,
    billing: false
  });
  const { success, error } = useToast();

  // Mock user data
  const [userData, setUserData] = useState({
    name: "Maria Silva Santos",
    email: "maria.santos@email.com",
    phone: "(11) 99999-8888",
    company: "Digital Marketing Pro",
    position: "Gerente de Marketing",
    website: "https://digitalmarketingpro.com.br",
    avatar: "https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face",
    accountType: "premium",
    memberSince: "Janeiro 2023",
    stats: {
      postsCreated: 247,
      platformsConnected: 5,
      scheduledPosts: 18,
      totalReach: "125K"
    }
  });

  const [preferences, setPreferences] = useState({
    theme: "system",
    language: "pt-BR",
    timezone: "America/Sao_Paulo",
    emailNotifications: true,
    pushNotifications: true,
    marketingEmails: false,
    weeklyReports: true,
    platformAlerts: {
      facebook: true,
      instagram: true,
      youtube: false,
      tiktok: true,
      twitter: false
    }
  });

  const [security, setSecurity] = useState({
    twoFactorEnabled: true,
    lastPasswordChange: "2024-11-15",
    activeSessions: 3
  });

  const [billing, setBilling] = useState({
    status: "active",
    plan: {
      name: "Plano Premium",
      description: "Acesso completo a todas as funcionalidades",
      price: 49.90
    },
    nextBilling: "2025-02-25",
    autoRenew: true,
    paymentMethod: {
      type: "Cartão de Crédito",
      brand: "Visa",
      last4: "4532",
      expiry: "12/26"
    },
    invoices: [
      {
        id: 1,
        number: "INV-2025-001",
        date: "2025-01-25",
        amount: 49.90,
        status: "paid"
      },
      {
        id: 2,
        number: "INV-2024-012",
        date: "2024-12-25",
        amount: 49.90,
        status: "paid"
      },
      {
        id: 3,
        number: "INV-2024-011",
        date: "2024-11-25",
        amount: 49.90,
        status: "paid"
      }
    ]
  });

  const handleDrawerToggle = () => {
    setIsDrawerOpen(!isDrawerOpen);
  };

  const handleSectionToggle = (section) => {
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev?.[section]
    }));
  };

  const handleImageUpload = (newImageUrl) => {
    setUserData(prev => ({ ...prev, avatar: newImageUrl }));
    success('Foto de perfil atualizada com sucesso!');
  };

  const handlePersonalInfoSave = (formData) => {
    setUserData(prev => ({ ...prev, ...formData }));
    success('Informações pessoais salvas com sucesso!');
  };

  const handlePreferencesSave = (formData) => {
    setPreferences(formData);
    success('Preferências salvas com sucesso!');
  };

  const handleSecuritySave = (data) => {
    if (data?.type === 'password') {
      setSecurity(prev => ({ 
        ...prev, 
        lastPasswordChange: new Date()?.toISOString()?.split('T')?.[0] 
      }));
      success('Senha alterada com sucesso!');
    } else if (data?.type === '2fa') {
      setSecurity(prev => ({ ...prev, twoFactorEnabled: data?.enabled }));
      success(data?.enabled ? 'Autenticação 2FA ativada!' : 'Autenticação 2FA desativada!');
    }
  };

  const handleBillingSave = (data) => {
    if (data?.type === 'payment') {
      success('Método de pagamento atualizado com sucesso!');
    }
  };

  return (
    <div className="min-h-screen bg-background">
      <GlobalHeader 
        user={userData}
        notificationCount={3}
        onDrawerToggle={handleDrawerToggle}
      />
      <NavigationDrawer 
        isOpen={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
      />
      <main className="container mx-auto px-4 sm:px-6 lg:px-8 pt-16 py-8">
        <BreadcrumbNavigation />

        <div className="space-y-6">
            <ProfileHeader 
              user={userData}
              onImageUpload={handleImageUpload}
            />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Main Content */}
              <div className="lg:col-span-2 space-y-4">
                <PersonalInfoSection
                  user={userData}
                  onSave={handlePersonalInfoSave}
                  isExpanded={expandedSections?.personal}
                  onToggle={() => handleSectionToggle('personal')}
                />

                <PreferencesSection
                  preferences={preferences}
                  onSave={handlePreferencesSave}
                  isExpanded={expandedSections?.preferences}
                  onToggle={() => handleSectionToggle('preferences')}
                />

                <SecuritySection
                  security={security}
                  onSave={handleSecuritySave}
                  isExpanded={expandedSections?.security}
                  onToggle={() => handleSectionToggle('security')}
                />
              </div>

              {/* Sidebar */}
              <div className="lg:col-span-1">
                <BillingSection
                  billing={billing}
                  onSave={handleBillingSave}
                  isExpanded={expandedSections?.billing}
                  onToggle={() => handleSectionToggle('billing')}
                />

                {/* Quick Actions */}
                <div className="bg-card border border-border rounded-lg p-6 mt-4">
                  <h3 className="font-body font-semibold text-lg text-primary mb-4">
                    Ações Rápidas
                  </h3>
                  <div className="space-y-3">
                    <a
                      href="/platform-connections"
                      className="flex items-center gap-3 p-3 rounded-lg hover:bg-muted transition-smooth"
                    >
                      <div className="w-8 h-8 bg-accent/10 rounded-lg flex items-center justify-center">
                        <Icon name="Link" size={16} className="text-accent" />
                      </div>
                      <div>
                        <div className="font-body font-medium text-primary text-sm">
                          Conectar Plataformas
                        </div>
                        <div className="text-xs text-muted-foreground font-body">
                          Gerencie suas conexões
                        </div>
                      </div>
                    </a>
                    
                    <a
                      href="/content-creator"
                      className="flex items-center gap-3 p-3 rounded-lg hover:bg-muted transition-smooth"
                    >
                      <div className="w-8 h-8 bg-success/10 rounded-lg flex items-center justify-center">
                        <Icon name="PenTool" size={16} className="text-success" />
                      </div>
                      <div>
                        <div className="font-body font-medium text-primary text-sm">
                          Criar Conteúdo
                        </div>
                        <div className="text-xs text-muted-foreground font-body">
                          Novo post ou campanha
                        </div>
                      </div>
                    </a>
                    
                    <a
                      href="/media-library"
                      className="flex items-center gap-3 p-3 rounded-lg hover:bg-muted transition-smooth"
                    >
                      <div className="w-8 h-8 bg-warning/10 rounded-lg flex items-center justify-center">
                        <Icon name="FolderOpen" size={16} className="text-warning" />
                      </div>
                      <div>
                        <div className="font-body font-medium text-primary text-sm">
                          Biblioteca de Mídia
                        </div>
                        <div className="text-xs text-muted-foreground font-body">
                          Organize seus arquivos
                        </div>
                      </div>
                    </a>
                  </div>
                </div>

                {/* Account Summary */}
                <div className="bg-muted/30 border border-border rounded-lg p-6 mt-4">
                  <h3 className="font-body font-semibold text-lg text-primary mb-4">
                    Resumo da Conta
                  </h3>
                  <div className="space-y-3 text-sm">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground font-body">Plano atual:</span>
                      <span className="font-body font-medium text-primary">Premium</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground font-body">Próxima cobrança:</span>
                      <span className="font-body font-medium text-primary">25/02/2025</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground font-body">Armazenamento:</span>
                      <span className="font-body font-medium text-primary">2.1GB / 10GB</span>
                    </div>
                    <div className="w-full bg-muted rounded-full h-2 mt-2">
                      <div className="bg-accent h-2 rounded-full" style={{ width: '21%' }}></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
      </main>
    </div>
  );
};

const UserProfile = () => {
  return (
    <ToastProvider>
      <UserProfileContent />
    </ToastProvider>
  );
};

export default UserProfile;