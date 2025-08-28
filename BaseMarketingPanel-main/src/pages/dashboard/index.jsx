import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import GlobalHeader from '../../components/ui/GlobalHeader';
import NavigationDrawer from '../../components/ui/NavigationDrawer';

import PlatformStatusCard from './components/PlatformStatusCard';
import ActivityFeed from './components/ActivityFeed';
import MetricsCard from './components/MetricsCard';
import QuickActionButton from './components/QuickActionButton';
import DashboardSkeleton from './components/DashboardSkeleton';

const Dashboard = () => {
  const navigate = useNavigate();
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [platforms, setPlatforms] = useState([]);
  const [activities, setActivities] = useState([]);
  const [metrics, setMetrics] = useState({});
  const [isLoading, setIsLoading] = useState(true);

  // Mock user data
  const mockUser = {
    name: "Maria Silva",
    email: "maria.silva@email.com",
    avatar: "https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150"
  };

  // Mock platform data
  const mockPlatforms = [
    {
      id: 'google-ads',
      name: 'Google Ads',
      icon: 'Search',
      status: 'connected',
      lastSync: new Date(Date.now() - 2 * 60 * 60 * 1000)?.toISOString(),
      health: 'healthy',
      accountInfo: {
        campaigns: 5,
        budget: 1500.00
      }
    },
    {
      id: 'meta',
      name: 'Meta (Facebook)',
      icon: 'Facebook',
      status: 'connected',
      lastSync: new Date(Date.now() - 30 * 60 * 1000)?.toISOString(),
      health: 'healthy',
      accountInfo: {
        pages: 2,
        followers: 12450
      }
    },
    {
      id: 'instagram',
      name: 'Instagram',
      icon: 'Instagram',
      status: 'connected',
      lastSync: new Date(Date.now() - 45 * 60 * 1000)?.toISOString(),
      health: 'healthy',
      accountInfo: {
        followers: 8920,
        engagement: 4.2
      }
    },
    {
      id: 'youtube',
      name: 'YouTube',
      icon: 'Youtube',
      status: 'error',
      lastSync: new Date(Date.now() - 12 * 60 * 60 * 1000)?.toISOString(),
      health: 'error',
      error: 'Token expirado',
      accountInfo: {
        subscribers: 2340,
        videos: 25
      }
    },
    {
      id: 'tiktok',
      name: 'TikTok',
      icon: 'Video',
      status: 'disconnected',
      lastSync: null,
      health: 'disconnected',
      accountInfo: null
    },
    {
      id: 'twitter',
      name: 'X (Twitter)',
      icon: 'Twitter',
      status: 'connected',
      lastSync: new Date(Date.now() - 4 * 60 * 60 * 1000)?.toISOString(),
      health: 'warning',
      warning: 'API limite próximo',
      accountInfo: {
        followers: 3420,
        tweets: 156
      }
    }
  ];

  // Mock activities data
  const mockActivities = [
    {
      id: 1,
      type: 'publication',
      platform: 'instagram',
      title: 'Post publicado com sucesso',
      description: 'Começando a semana com energia positiva! ✨',
      timestamp: new Date(Date.now() - 30 * 60 * 1000)?.toISOString(),
      status: 'success',
      engagement: {
        likes: 156,
        comments: 23,
        shares: 12
      }
    },
    {
      id: 2,
      type: 'scheduled',
      platform: 'facebook',
      title: 'Post agendado para hoje às 14:30',
      description: '🎯 Dica de Marketing Digital...',
      timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000)?.toISOString(),
      status: 'pending'
    },
    {
      id: 3,
      type: 'system',
      platform: null,
      title: 'Token do YouTube expirado',
      description: 'Reconecte sua conta para continuar publicando',
      timestamp: new Date(Date.now() - 4 * 60 * 60 * 1000)?.toISOString(),
      status: 'error',
      action: {
        label: 'Reconectar',
        path: '/platform-connections'
      }
    },
    {
      id: 4,
      type: 'publication',
      platform: 'youtube',
      title: 'Vídeo publicado',
      description: 'Como criar conteúdo que converte em 2025',
      timestamp: new Date(Date.now() - 8 * 60 * 60 * 1000)?.toISOString(),
      status: 'success',
      engagement: {
        views: 1240,
        likes: 89,
        comments: 15
      }
    },
    {
      id: 5,
      type: 'scheduled',
      platform: 'tiktok',
      title: 'Post agendado para amanhã',
      description: 'POV: Você descobriu o segredo para criar conteúdo viral 🤫',
      timestamp: new Date(Date.now() - 12 * 60 * 60 * 1000)?.toISOString(),
      status: 'pending'
    }
  ];

  // Mock metrics data
  const mockMetrics = {
    totalFollowers: 27130,
    avgEngagementRate: 4.7,
    scheduledPosts: 12,
    monthlyReach: 45200,
    totalSpent: 2840.50,
    conversion: 3.2
  };

  // Load dashboard data
  useEffect(() => {
    const loadDashboardData = () => {
      setIsLoading(true);
      // Simulate API call
      setTimeout(() => {
        setPlatforms(mockPlatforms);
        setActivities(mockActivities);
        setMetrics(mockMetrics);
        setIsLoading(false);
      }, 1500);
    };

    loadDashboardData();
  }, []);

  const handleQuickAction = () => {
    navigate('/content-creator');
  };

  const handlePlatformReconnect = (platformId) => {
    navigate('/platform-connections', { state: { focusPlatform: platformId } });
  };

  const handleActivityAction = (action) => {
    if (action?.path) {
      navigate(action?.path);
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-background">
        <GlobalHeader
          user={mockUser}
          notificationCount={3}
          onDrawerToggle={() => setIsDrawerOpen(true)}
        />
        <NavigationDrawer
          isOpen={isDrawerOpen}
          onClose={() => setIsDrawerOpen(false)}
        />
        <main className="container mx-auto px-4 sm:px-6 lg:px-8 pt-16 py-4">
          <DashboardSkeleton />
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Global Header */}
      <GlobalHeader
        user={mockUser}
        notificationCount={3}
        onDrawerToggle={() => setIsDrawerOpen(true)}
      />

      {/* Navigation Drawer */}
      <NavigationDrawer
        isOpen={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
      />

      {/* Main Content */}
      <main className="container mx-auto px-4 sm:px-6 lg:px-8 pt-16">
        {/* Welcome Section */}
        <div className="py-6 border-b border-border">
          <div className="max-w-3xl">
            <h1 className="text-2xl lg:text-3xl font-body font-bold text-foreground mb-2">
              Olá, {mockUser?.name?.split(' ')?.[0] || 'Usuário'} 👋
            </h1>
            <p className="text-muted-foreground font-body">
              Bem-vindo ao seu painel de controle. Aqui você pode monitorar suas conexões,
              atividades recentes e métricas de performance em um só lugar.
            </p>
          </div>
        </div>

        {/* Dashboard Content */}
        <div className="py-6">
          {/* Mobile Layout */}
          <div className="lg:hidden space-y-6">
              {/* Platform Status Cards */}
              <section>
                <h2 className="text-lg font-body font-semibold text-foreground mb-4">
                  Status das Plataformas
                </h2>
                <div className="space-y-3">
                  {platforms?.map((platform) => (
                    <PlatformStatusCard
                      key={platform?.id}
                      platform={platform}
                      onReconnect={() => handlePlatformReconnect(platform?.id)}
                      compact={true}
                    />
                  ))}
                </div>
              </section>

              {/* Key Metrics */}
              <section>
                <h2 className="text-lg font-body font-semibold text-foreground mb-4">
                  Métricas Principais
                </h2>
                <div className="grid grid-cols-2 gap-3">
                  <MetricsCard
                    title="Seguidores"
                    value={metrics?.totalFollowers}
                    format="number"
                    icon="Users"
                    trend="+12.5%"
                    description="Crescimento recente"
                    compact={true}
                  />
                  <MetricsCard
                    title="Engajamento"
                    value={metrics?.avgEngagementRate}
                    format="percentage"
                    icon="Heart"
                    trend="+0.8%"
                    description="Taxa média"
                    compact={true}
                  />
                  <MetricsCard
                    title="Posts Agendados"
                    value={metrics?.scheduledPosts}
                    format="number"
                    icon="Calendar"
                    trend=""
                    description="Próximos posts"
                    compact={true}
                  />
                  <MetricsCard
                    title="Gasto (BRL)"
                    value={metrics?.totalSpent}
                    format="currency"
                    icon="DollarSign"
                    trend="+15.2%"
                    description="Investimento mensal"
                    compact={true}
                  />
                </div>
              </section>

              {/* Activity Feed */}
              <section>
                <h2 className="text-lg font-body font-semibold text-foreground mb-4">
                  Atividades Recentes
                </h2>
                <ActivityFeed
                  activities={activities}
                  onActionClick={handleActivityAction}
                  showLimit={5}
                  compact={true}
                />
              </section>
            </div>

            {/* Desktop Layout */}
            <div className="hidden lg:block">
              <div className="grid grid-cols-12 gap-6">
                {/* Left Column */}
                <div className="col-span-8 space-y-6">
                  {/* Platform Status Grid */}
                  <section>
                    <h2 className="text-xl font-body font-semibold text-foreground mb-4">
                      Status das Plataformas
                    </h2>
                    <div className="grid grid-cols-2 xl:grid-cols-3 gap-4">
                      {platforms?.map((platform) => (
                        <PlatformStatusCard
                          key={platform?.id}
                          platform={platform}
                          onReconnect={() => handlePlatformReconnect(platform?.id)}
                        />
                      ))}
                    </div>
                  </section>

                  {/* Activity Feed */}
                  <section>
                    <h2 className="text-xl font-body font-semibold text-foreground mb-4">
                      Atividades Recentes
                    </h2>
                    <ActivityFeed
                      activities={activities}
                      onActionClick={handleActivityAction}
                      showLimit={8}
                    />
                  </section>
                </div>

                {/* Right Sidebar */}
                <div className="col-span-4 space-y-6">
                  {/* Metrics Dashboard */}
                  <section>
                    <h2 className="text-xl font-body font-semibold text-foreground mb-4">
                      Métricas Principais
                    </h2>
                    <div className="space-y-4">
                      <MetricsCard
                        title="Total de Seguidores"
                        value={metrics?.totalFollowers}
                        format="number"
                        icon="Users"
                        trend="+12.5%"
                        description="Crescimento nos últimos 30 dias"
                      />
                      <MetricsCard
                        title="Taxa de Engajamento"
                        value={metrics?.avgEngagementRate}
                        format="percentage"
                        icon="Heart"
                        trend="+0.8%"
                        description="Média das últimas 2 semanas"
                      />
                      <MetricsCard
                        title="Posts Agendados"
                        value={metrics?.scheduledPosts}
                        format="number"
                        icon="Calendar"
                        description="Para os próximos 7 dias"
                      />
                      <MetricsCard
                        title="Alcance Mensal"
                        value={metrics?.monthlyReach}
                        format="number"
                        icon="TrendingUp"
                        trend="+22.3%"
                        description="Pessoas alcançadas este mês"
                      />
                      <MetricsCard
                        title="Investimento (BRL)"
                        value={metrics?.totalSpent}
                        format="currency"
                        icon="DollarSign"
                        trend="+15.2%"
                        description="Gasto em anúncios este mês"
                      />
                      <MetricsCard
                        title="Taxa de Conversão"
                        value={metrics?.conversion}
                        format="percentage"
                        icon="Target"
                        trend="+0.5%"
                        description="Conversões dos últimos 30 dias"
                      />
                    </div>
                  </section>
                </div>
              </div>
            </div>
          </div>
      </main>

      {/* Quick Action Button */}
      <QuickActionButton onClick={handleQuickAction} />
    </div>
  );
};

export default Dashboard;