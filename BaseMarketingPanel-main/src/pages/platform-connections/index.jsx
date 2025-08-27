import React, { useState, useEffect } from 'react';
import GlobalHeader from '../../components/ui/GlobalHeader';
import NavigationDrawer from '../../components/ui/NavigationDrawer';
import BreadcrumbNavigation from '../../components/ui/BreadcrumbNavigation';
import { ToastProvider } from '../../components/ui/ToastNotificationSystem';
import PlatformCard from './components/PlatformCard';
import ConnectionModal from './components/ConnectionModal';
import PlatformSettings from './components/PlatformSettings';
import DisconnectModal from './components/DisconnectModal';
import ConnectionStats from './components/ConnectionStats';
import Icon from '../../components/AppIcon';
import Button from '../../components/ui/Button';

const PlatformConnections = () => {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [selectedPlatform, setSelectedPlatform] = useState(null);
  const [connectionModal, setConnectionModal] = useState(false);
  const [settingsModal, setSettingsModal] = useState(false);
  const [disconnectModal, setDisconnectModal] = useState(false);
  const [platforms, setPlatforms] = useState([
    {
      id: 'google-ads',
      name: 'Google Ads',
      description: 'Gerencie campanhas publicitárias no Google',
      logo: 'https://images.unsplash.com/photo-1573804633927-bfcbcd909acd?w=100&h=100&fit=crop&crop=center',
      isConnected: true,
      accountInfo: {
        username: 'marketing@empresa.com',
        email: 'marketing@empresa.com'
      },
      lastSync: '25/08/2025 às 11:30',
      stats: {
        posts: '12',
        followers: '2.5K',
        engagement: '4.2%'
      },
      settings: {
        autoPost: true,
        defaultHashtags: '#marketing #googleads #publicidade',
        postFormat: 'standard',
        scheduleTime: '09:00',
        enableAnalytics: true,
        contentApproval: false,
        maxHashtags: 10,
        imageQuality: 'high'
      }
    },
    {
      id: 'meta',
      name: 'Meta (Facebook)',
      description: 'Publique e gerencie conteúdo no Facebook',
      logo: 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=100&h=100&fit=crop&crop=center',
      isConnected: true,
      accountInfo: {
        username: 'Empresa Marketing',
        email: 'social@empresa.com'
      },
      lastSync: '25/08/2025 às 10:45',
      stats: {
        posts: '45',
        followers: '8.7K',
        engagement: '6.8%'
      },
      settings: {
        autoPost: true,
        defaultHashtags: '#facebook #socialmedia #marketing',
        postFormat: 'standard',
        scheduleTime: '10:00',
        enableAnalytics: true,
        contentApproval: true,
        maxHashtags: 15,
        imageQuality: 'high'
      }
    },
    {
      id: 'instagram',
      name: 'Instagram',
      description: 'Compartilhe fotos e stories no Instagram',
      logo: 'https://images.unsplash.com/photo-1611262588024-d12430b98920?w=100&h=100&fit=crop&crop=center',
      isConnected: false,
      accountInfo: null,
      lastSync: null,
      stats: null,
      settings: {
        autoPost: false,
        defaultHashtags: '#instagram #photo #lifestyle',
        postFormat: 'standard',
        scheduleTime: '12:00',
        enableAnalytics: true,
        contentApproval: false,
        maxHashtags: 30,
        imageQuality: 'high',
        crossPost: false,
        addLocation: true
      }
    },
    {
      id: 'youtube',
      name: 'YouTube',
      description: 'Publique e gerencie vídeos no YouTube',
      logo: 'https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?w=100&h=100&fit=crop&crop=center',
      isConnected: true,
      accountInfo: {
        username: 'Canal Empresa',
        email: 'youtube@empresa.com'
      },
      lastSync: '24/08/2025 às 16:20',
      stats: {
        posts: '23',
        followers: '15.2K',
        engagement: '8.9%'
      },
      settings: {
        autoPost: false,
        defaultHashtags: '#youtube #video #content',
        postFormat: 'standard',
        scheduleTime: '14:00',
        enableAnalytics: true,
        contentApproval: true,
        maxHashtags: 20,
        imageQuality: 'high',
        defaultPrivacy: 'public',
        autoMonetize: true
      }
    },
    {
      id: 'tiktok',
      name: 'TikTok',
      description: 'Crie e publique vídeos curtos no TikTok',
      logo: 'https://images.unsplash.com/photo-1611605698335-8b1569810432?w=100&h=100&fit=crop&crop=center',
      isConnected: false,
      accountInfo: null,
      lastSync: null,
      stats: null,
      settings: {
        autoPost: false,
        defaultHashtags: '#tiktok #viral #trending',
        postFormat: 'reel',
        scheduleTime: '18:00',
        enableAnalytics: true,
        contentApproval: false,
        maxHashtags: 25,
        imageQuality: 'high'
      }
    },
    {
      id: 'twitter',
      name: 'X (Twitter)',
      description: 'Publique tweets e gerencie sua presença no X',
      logo: 'https://images.unsplash.com/photo-1611605698323-b1e99cfd37ea?w=100&h=100&fit=crop&crop=center',
      isConnected: false,
      accountInfo: null,
      lastSync: null,
      stats: null,
      settings: {
        autoPost: false,
        defaultHashtags: '#twitter #socialmedia #news',
        postFormat: 'standard',
        scheduleTime: '08:00',
        enableAnalytics: true,
        contentApproval: false,
        maxHashtags: 10,
        imageQuality: 'medium'
      }
    }
  ]);

  const user = {
    name: 'Maria Silva',
    email: 'maria@empresa.com'
  };

  const breadcrumbs = [
    { label: 'Início', path: '/dashboard', isHome: true },
    { label: 'Conexões', path: '/platform-connections', isActive: true }
  ];

  useEffect(() => {
    document.title = 'Conexões de Plataformas - Marketing Panel';
  }, []);

  const handleConnect = (platformId, credentials) => {
    setPlatforms(prev => prev?.map(platform => {
      if (platform?.id === platformId) {
        return {
          ...platform,
          isConnected: true,
          accountInfo: {
            username: `Conta ${platform?.name}`,
            email: 'conta@empresa.com'
          },
          lastSync: new Date()?.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          }),
          stats: {
            posts: '0',
            followers: '1.2K',
            engagement: '2.1%'
          }
        };
      }
      return platform;
    }));
  };

  const handleDisconnect = (platformId, options) => {
    setPlatforms(prev => prev?.map(platform => {
      if (platform?.id === platformId) {
        return {
          ...platform,
          isConnected: false,
          accountInfo: null,
          lastSync: null,
          stats: options?.keepData ? platform?.stats : null
        };
      }
      return platform;
    }));
  };

  const handleShowSettings = (platformId) => {
    const platform = platforms?.find(p => p?.id === platformId);
    setSelectedPlatform(platform);
    setSettingsModal(true);
  };

  const handleSaveSettings = (platformId, newSettings) => {
    setPlatforms(prev => prev?.map(platform => {
      if (platform?.id === platformId) {
        return {
          ...platform,
          settings: { ...platform?.settings, ...newSettings }
        };
      }
      return platform;
    }));
  };

  const handleShowConnectionModal = (platformId) => {
    const platform = platforms?.find(p => p?.id === platformId);
    setSelectedPlatform(platform);
    setConnectionModal(true);
  };

  const handleShowDisconnectModal = (platformId) => {
    const platform = platforms?.find(p => p?.id === platformId);
    setSelectedPlatform(platform);
    setDisconnectModal(true);
  };

  const handleConnectAll = () => {
    const disconnectedPlatforms = platforms?.filter(p => !p?.isConnected);
    if (disconnectedPlatforms?.length > 0) {
      const firstDisconnected = disconnectedPlatforms?.[0];
      handleShowConnectionModal(firstDisconnected?.id);
    }
  };

  return (
    <ToastProvider>
      <div className="min-h-screen bg-background">
        <GlobalHeader 
          user={user}
          notificationCount={3}
          onDrawerToggle={() => setIsDrawerOpen(true)}
        />
        
        <NavigationDrawer 
          isOpen={isDrawerOpen}
          onClose={() => setIsDrawerOpen(false)}
        />

        <main className="container mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">
          <BreadcrumbNavigation customBreadcrumbs={breadcrumbs} />

          {/* Page Header */}
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
              <h1 className="font-body font-bold text-3xl text-foreground mb-2">
                Conexões de Plataformas
              </h1>
              <p className="text-muted-foreground font-body">
                Gerencie suas conexões com redes sociais e plataformas de marketing
              </p>
            </div>

            <div className="flex items-center gap-3 mt-4 sm:mt-0">
              <Button
                variant="outline"
                iconName="RefreshCw"
                iconPosition="left"
                onClick={() => window.location?.reload()}
              >
                Atualizar
              </Button>
              <Button
                variant="default"
                iconName="Plus"
                iconPosition="left"
                onClick={handleConnectAll}
              >
                Conectar Todas
              </Button>
            </div>
          </div>

          {/* Quick Actions */}
          <div className="bg-card border border-border rounded-lg p-6 mb-8">
            <h2 className="font-body font-semibold text-lg text-card-foreground mb-4">
              Ações Rápidas
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <Button
                  variant="outline"
                  iconName="Zap"
                  iconPosition="left"
                  className="justify-start"
                  onClick={() => console.log('Test all connections')}
                >
                  Testar Conexões
                </Button>
                <Button
                  variant="outline"
                  iconName="Download"
                  iconPosition="left"
                  className="justify-start"
                  onClick={() => console.log('Export settings')}
                >
                  Exportar Configurações
                </Button>
                <Button
                  variant="outline"
                  iconName="Upload"
                  iconPosition="left"
                  className="justify-start"
                  onClick={() => console.log('Import settings')}
                >
                  Importar Configurações
                </Button>
                <Button
                  variant="outline"
                  iconName="HelpCircle"
                  iconPosition="left"
                  className="justify-start"
                  onClick={() => window.open('/help/connections', '_blank')}
                >
                  Ajuda
                </Button>
              </div>
            </div>

            {/* Platform Cards Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {platforms?.map((platform) => (
                <PlatformCard
                  key={platform?.id}
                  platform={platform}
                  onConnect={handleShowConnectionModal}
                  onDisconnect={handleShowDisconnectModal}
                  onShowSettings={handleShowSettings}
                />
              ))}
            </div>

            {/* Help Section */}
            <div className="mt-12 bg-accent/5 border border-accent/20 rounded-lg p-6">
              <div className="flex items-start gap-4">
                <div className="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Icon name="Info" size={24} className="text-accent" />
                </div>
                <div>
                  <h3 className="font-body font-semibold text-lg text-card-foreground mb-2">
                    Precisa de Ajuda?
                  </h3>
                  <p className="text-muted-foreground font-body mb-4">
                    Consulte nossa documentação para obter instruções detalhadas sobre como conectar cada plataforma.
                  </p>
                  <div className="flex flex-col sm:flex-row gap-3">
                    <Button
                      variant="outline"
                      iconName="Book"
                      iconPosition="left"
                      onClick={() => window.open('/docs/connections', '_blank')}
                    >
                      Ver Documentação
                    </Button>
                    <Button
                      variant="outline"
                      iconName="MessageCircle"
                      iconPosition="left"
                      onClick={() => window.open('/support', '_blank')}
                    >
                      Contatar Suporte
                    </Button>
                  </div>
                </div>
              </div>
            </div>
        </main>

        {/* Modals */}
        <ConnectionModal
          platform={selectedPlatform}
          isOpen={connectionModal}
          onClose={() => {
            setConnectionModal(false);
            setSelectedPlatform(null);
          }}
          onConnect={handleConnect}
        />

        <PlatformSettings
          platform={selectedPlatform}
          isOpen={settingsModal}
          onClose={() => {
            setSettingsModal(false);
            setSelectedPlatform(null);
          }}
          onSave={handleSaveSettings}
        />

        <DisconnectModal
          platform={selectedPlatform}
          isOpen={disconnectModal}
          onClose={() => {
            setDisconnectModal(false);
            setSelectedPlatform(null);
          }}
          onConfirm={handleDisconnect}
        />
      </div>
    </ToastProvider>
  );
};

export default PlatformConnections;