import React from 'react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';

const PlatformStatusCard = ({ platform, onReconnect, compact = false }) => {
  const getStatusColor = (status, health) => {
    if (status === 'disconnected') return 'bg-muted text-muted-foreground';
    if (health === 'error') return 'bg-error text-error-foreground';
    if (health === 'warning') return 'bg-warning text-warning-foreground';
    return 'bg-success text-success-foreground';
  };

  const getStatusText = (status, health) => {
    if (status === 'disconnected') return 'Desconectado';
    if (health === 'error') return 'Erro';
    if (health === 'warning') return 'Atenção';
    return 'Conectado';
  };

  const formatLastSync = (timestamp) => {
    if (!timestamp) return 'Nunca';
    
    try {
      const date = new Date(timestamp);
      const now = new Date();
      const diffInMinutes = Math.floor((now - date) / (1000 * 60));
      
      if (diffInMinutes < 1) return 'Agora mesmo';
      if (diffInMinutes < 60) return `${diffInMinutes}min atrás`;
      if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h atrás`;
      
      return format(date, "dd/MM/yyyy 'às' HH:mm", { 
        locale: ptBR,
        timeZone: 'America/Sao_Paulo'
      });
    } catch {
      return 'Data inválida';
    }
  };

  const getPlatformIcon = (iconName) => {
    const iconMap = {
      'Facebook': 'Facebook',
      'Instagram': 'Instagram', 
      'Youtube': 'Youtube',
      'Twitter': 'Twitter',
      'Video': 'Video',
      'Search': 'Search'
    };
    return iconMap?.[iconName] || 'Globe';
  };

  if (compact) {
    return (
      <div className="bg-card border border-border rounded-lg p-4 hover:shadow-card transition-smooth">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 bg-accent rounded-lg flex items-center justify-center">
              <Icon name={getPlatformIcon(platform?.icon)} size={16} color="white" />
            </div>
            <div>
              <h3 className="font-body font-medium text-sm text-foreground">
                {platform?.name}
              </h3>
            </div>
          </div>
          <div className={`px-2 py-1 rounded-full text-xs font-body font-medium ${getStatusColor(platform?.status, platform?.health)}`}>
            {getStatusText(platform?.status, platform?.health)}
          </div>
        </div>

        {/* Account Info for Connected Platforms */}
        {platform?.status === 'connected' && platform?.accountInfo && (
          <div className="mb-3">
            <div className="flex items-center justify-between text-xs text-muted-foreground font-body">
              {platform?.id === 'google-ads' && (
                <>
                  <span>{platform?.accountInfo?.campaigns} campanhas</span>
                  <span>R$ {platform?.accountInfo?.budget?.toFixed(2)}</span>
                </>
              )}
              {platform?.id === 'meta' && (
                <>
                  <span>{platform?.accountInfo?.pages} páginas</span>
                  <span>{platform?.accountInfo?.followers?.toLocaleString()} seguidores</span>
                </>
              )}
              {platform?.id === 'instagram' && (
                <>
                  <span>{platform?.accountInfo?.followers?.toLocaleString()} seguidores</span>
                  <span>{platform?.accountInfo?.engagement}% engajamento</span>
                </>
              )}
              {platform?.id === 'youtube' && (
                <>
                  <span>{platform?.accountInfo?.subscribers?.toLocaleString()} inscritos</span>
                  <span>{platform?.accountInfo?.videos} vídeos</span>
                </>
              )}
              {platform?.id === 'twitter' && (
                <>
                  <span>{platform?.accountInfo?.followers?.toLocaleString()} seguidores</span>
                  <span>{platform?.accountInfo?.tweets} tweets</span>
                </>
              )}
            </div>
          </div>
        )}

        {/* Error/Warning Messages */}
        {platform?.error && (
          <div className="mb-3 text-xs text-error font-body">
            {platform?.error}
          </div>
        )}
        {platform?.warning && (
          <div className="mb-3 text-xs text-warning font-body">
            {platform?.warning}
          </div>
        )}

        <div className="flex items-center justify-between">
          <span className="text-xs text-muted-foreground font-body">
            {formatLastSync(platform?.lastSync)}
          </span>
          {(platform?.status === 'disconnected' || platform?.health === 'error') && (
            <Button
              size="xs"
              variant="outline"
              onClick={() => onReconnect?.(platform?.id)}
            >
              {platform?.status === 'disconnected' ? 'Conectar' : 'Reconectar'}
            </Button>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="bg-card border border-border rounded-lg p-6 hover:shadow-card transition-smooth">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-4">
          <div className="w-12 h-12 bg-accent rounded-lg flex items-center justify-center">
            <Icon name={getPlatformIcon(platform?.icon)} size={24} color="white" />
          </div>
          <div>
            <h3 className="font-body font-medium text-foreground">
              {platform?.name}
            </h3>
            <p className="text-sm text-muted-foreground font-body">
              Última sinc: {formatLastSync(platform?.lastSync)}
            </p>
          </div>
        </div>
        <div className={`px-3 py-1.5 rounded-full text-sm font-body font-medium ${getStatusColor(platform?.status, platform?.health)}`}>
          {getStatusText(platform?.status, platform?.health)}
        </div>
      </div>

      {/* Account Info for Connected Platforms */}
      {platform?.status === 'connected' && platform?.accountInfo && (
        <div className="mb-4 p-3 bg-muted/30 rounded-lg">
          <div className="grid grid-cols-2 gap-4 text-sm">
            {platform?.id === 'google-ads' && (
              <>
                <div>
                  <span className="text-muted-foreground font-body">Campanhas</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.campaigns}
                  </div>
                </div>
                <div>
                  <span className="text-muted-foreground font-body">Orçamento</span>
                  <div className="font-body font-medium text-foreground">
                    R$ {platform?.accountInfo?.budget?.toFixed(2)}
                  </div>
                </div>
              </>
            )}
            {platform?.id === 'meta' && (
              <>
                <div>
                  <span className="text-muted-foreground font-body">Páginas</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.pages}
                  </div>
                </div>
                <div>
                  <span className="text-muted-foreground font-body">Seguidores</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.followers?.toLocaleString()}
                  </div>
                </div>
              </>
            )}
            {platform?.id === 'instagram' && (
              <>
                <div>
                  <span className="text-muted-foreground font-body">Seguidores</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.followers?.toLocaleString()}
                  </div>
                </div>
                <div>
                  <span className="text-muted-foreground font-body">Engajamento</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.engagement}%
                  </div>
                </div>
              </>
            )}
            {platform?.id === 'youtube' && (
              <>
                <div>
                  <span className="text-muted-foreground font-body">Inscritos</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.subscribers?.toLocaleString()}
                  </div>
                </div>
                <div>
                  <span className="text-muted-foreground font-body">Vídeos</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.videos}
                  </div>
                </div>
              </>
            )}
            {platform?.id === 'twitter' && (
              <>
                <div>
                  <span className="text-muted-foreground font-body">Seguidores</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.followers?.toLocaleString()}
                  </div>
                </div>
                <div>
                  <span className="text-muted-foreground font-body">Tweets</span>
                  <div className="font-body font-medium text-foreground">
                    {platform?.accountInfo?.tweets}
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      )}

      {/* Error/Warning Messages */}
      {platform?.error && (
        <div className="mb-4 p-3 bg-error/10 border border-error/20 rounded-lg">
          <div className="flex items-center gap-2 text-error">
            <Icon name="AlertCircle" size={16} />
            <span className="font-body font-medium">{platform?.error}</span>
          </div>
        </div>
      )}
      {platform?.warning && (
        <div className="mb-4 p-3 bg-warning/10 border border-warning/20 rounded-lg">
          <div className="flex items-center gap-2 text-warning">
            <Icon name="AlertTriangle" size={16} />
            <span className="font-body font-medium">{platform?.warning}</span>
          </div>
        </div>
      )}

      {/* Action Button */}
      {(platform?.status === 'disconnected' || platform?.health === 'error') && (
        <Button
          variant="outline"
          size="sm"
          fullWidth
          onClick={() => onReconnect?.(platform?.id)}
          iconName="RefreshCw"
        >
          {platform?.status === 'disconnected' ? 'Conectar Plataforma' : 'Reconectar'}
        </Button>
      )}
    </div>
  );
};

export default PlatformStatusCard;