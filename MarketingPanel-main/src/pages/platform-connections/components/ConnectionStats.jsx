import React from 'react';
import Icon from '../../../components/AppIcon';

const ConnectionStats = ({ platforms }) => {
  const connectedCount = platforms?.filter(p => p?.isConnected)?.length;
  const totalCount = platforms?.length;
  const connectionRate = Math.round((connectedCount / totalCount) * 100);

  const getStatusColor = () => {
    if (connectionRate >= 80) return 'text-success';
    if (connectionRate >= 50) return 'text-warning';
    return 'text-error';
  };

  const getStatusIcon = () => {
    if (connectionRate >= 80) return 'CheckCircle';
    if (connectionRate >= 50) return 'AlertTriangle';
    return 'XCircle';
  };

  const totalFollowers = platforms?.filter(p => p?.isConnected && p?.stats)?.reduce((sum, p) => sum + parseInt(p?.stats?.followers?.replace(/[^\d]/g, '')), 0);

  const totalPosts = platforms?.filter(p => p?.isConnected && p?.stats)?.reduce((sum, p) => sum + parseInt(p?.stats?.posts), 0);

  const formatNumber = (num) => {
    if (num >= 1000000) return `${(num / 1000000)?.toFixed(1)}M`;
    if (num >= 1000) return `${(num / 1000)?.toFixed(1)}K`;
    return num?.toString();
  };

  return (
    <div className="bg-card border border-border rounded-lg p-6 mb-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="font-body font-semibold text-lg text-card-foreground">
          Resumo das Conexões
        </h2>
        <div className={`flex items-center gap-2 ${getStatusColor()}`}>
          <Icon name={getStatusIcon()} size={20} />
          <span className="font-medium text-sm">{connectionRate}% Conectado</span>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        {/* Connected Platforms */}
        <div className="text-center">
          <div className="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mx-auto mb-3">
            <Icon name="Link" size={24} className="text-accent" />
          </div>
          <div className="text-2xl font-bold text-card-foreground mb-1">
            {connectedCount}/{totalCount}
          </div>
          <div className="text-sm text-muted-foreground">
            Plataformas Conectadas
          </div>
        </div>

        {/* Total Followers */}
        <div className="text-center">
          <div className="w-12 h-12 bg-success/10 rounded-lg flex items-center justify-center mx-auto mb-3">
            <Icon name="Users" size={24} className="text-success" />
          </div>
          <div className="text-2xl font-bold text-card-foreground mb-1">
            {formatNumber(totalFollowers)}
          </div>
          <div className="text-sm text-muted-foreground">
            Total de Seguidores
          </div>
        </div>

        {/* Total Posts */}
        <div className="text-center">
          <div className="w-12 h-12 bg-warning/10 rounded-lg flex items-center justify-center mx-auto mb-3">
            <Icon name="FileText" size={24} className="text-warning" />
          </div>
          <div className="text-2xl font-bold text-card-foreground mb-1">
            {totalPosts}
          </div>
          <div className="text-sm text-muted-foreground">
            Posts Publicados
          </div>
        </div>
      </div>

      {/* Progress Bar */}
      <div className="mt-6">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm font-medium text-card-foreground">
            Progresso de Conexão
          </span>
          <span className="text-sm text-muted-foreground">
            {connectedCount} de {totalCount} conectadas
          </span>
        </div>
        <div className="w-full bg-muted rounded-full h-2">
          <div 
            className="bg-accent rounded-full h-2 transition-smooth"
            style={{ width: `${connectionRate}%` }}
          ></div>
        </div>
      </div>
    </div>
  );
};

export default ConnectionStats;