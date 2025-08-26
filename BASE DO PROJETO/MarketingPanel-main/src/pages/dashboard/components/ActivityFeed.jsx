import React from 'react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';

const ActivityFeed = ({ activities = [], onActionClick, showLimit = null, compact = false }) => {
  const displayActivities = showLimit ? activities?.slice(0, showLimit) : activities;

  const getPlatformIcon = (platform) => {
    const platformIcons = {
      'instagram': 'Instagram',
      'facebook': 'Facebook',
      'youtube': 'Youtube',
      'twitter': 'Twitter',
      'tiktok': 'Video',
      'google-ads': 'Search'
    };
    return platformIcons?.[platform] || 'Bell';
  };

  const getActivityIcon = (type, status) => {
    if (type === 'publication') {
      return status === 'success' ? 'CheckCircle' : 'XCircle';
    }
    if (type === 'scheduled') return 'Clock';
    if (type === 'system') {
      return status === 'error' ? 'AlertCircle' : 'Info';
    }
    return 'Bell';
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'success': return 'text-success';
      case 'error': return 'text-error';
      case 'warning': return 'text-warning';
      case 'pending': return 'text-muted-foreground';
      default: return 'text-foreground';
    }
  };

  const formatTimestamp = (timestamp) => {
    try {
      const date = new Date(timestamp);
      const now = new Date();
      const diffInMinutes = Math.floor((now - date) / (1000 * 60));
      
      if (diffInMinutes < 1) return 'Agora mesmo';
      if (diffInMinutes < 60) return `${diffInMinutes}min atrás`;
      if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h atrás`;
      
      return format(date, "dd/MM 'às' HH:mm", { 
        locale: ptBR,
        timeZone: 'America/Sao_Paulo'
      });
    } catch {
      return 'Data inválida';
    }
  };

  const formatEngagement = (engagement) => {
    if (!engagement) return null;
    
    const parts = [];
    if (engagement?.likes) parts?.push(`${engagement?.likes} likes`);
    if (engagement?.comments) parts?.push(`${engagement?.comments} comentários`);
    if (engagement?.shares) parts?.push(`${engagement?.shares} compartilhamentos`);
    if (engagement?.views) parts?.push(`${engagement?.views} visualizações`);
    
    return parts?.join(' • ');
  };

  if (!displayActivities?.length) {
    return (
      <div className="bg-card border border-border rounded-lg p-6 text-center">
        <Icon name="Activity" size={48} className="text-muted-foreground mx-auto mb-3" />
        <h3 className="font-body font-medium text-foreground mb-2">
          Nenhuma atividade recente
        </h3>
        <p className="text-sm text-muted-foreground font-body">
          Quando você publicar conteúdo ou agendar posts, as atividades aparecerão aqui.
        </p>
      </div>
    );
  }

  return (
    <div className="bg-card border border-border rounded-lg p-4 lg:p-6">
      <div className="space-y-4">
        {displayActivities?.map((activity, index) => (
          <div
            key={activity?.id}
            className={`flex gap-3 ${index !== displayActivities?.length - 1 ? 'pb-4 border-b border-border' : ''}`}
          >
            {/* Platform Icon */}
            <div className="flex-shrink-0">
              {activity?.platform ? (
                <div className="w-8 h-8 bg-accent rounded-lg flex items-center justify-center">
                  <Icon name={getPlatformIcon(activity?.platform)} size={16} color="white" />
                </div>
              ) : (
                <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${getStatusColor(activity?.status)} bg-current/10`}>
                  <Icon name={getActivityIcon(activity?.type, activity?.status)} size={16} className={getStatusColor(activity?.status)} />
                </div>
              )}
            </div>

            {/* Content */}
            <div className="flex-1 min-w-0">
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <h4 className={`font-body font-medium ${compact ? 'text-sm' : 'text-base'} text-foreground mb-1`}>
                    {activity?.title}
                  </h4>
                  
                  {activity?.description && (
                    <p className={`${compact ? 'text-xs' : 'text-sm'} text-muted-foreground font-body mb-2 line-clamp-2`}>
                      {activity?.description}
                    </p>
                  )}

                  {/* Engagement Metrics */}
                  {activity?.engagement && (
                    <div className={`${compact ? 'text-xs' : 'text-sm'} text-muted-foreground font-body mb-2`}>
                      {formatEngagement(activity?.engagement)}
                    </div>
                  )}

                  <div className="flex items-center justify-between">
                    <span className={`${compact ? 'text-xs' : 'text-sm'} text-muted-foreground font-body`}>
                      {formatTimestamp(activity?.timestamp)}
                    </span>

                    {/* Action Button */}
                    {activity?.action && (
                      <Button
                        size="xs"
                        variant="outline"
                        onClick={() => onActionClick?.(activity?.action)}
                      >
                        {activity?.action?.label}
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Show More Link */}
      {showLimit && activities?.length > showLimit && (
        <div className="mt-4 pt-4 border-t border-border text-center">
          <Button
            variant="ghost"
            size="sm"
            iconName="ChevronDown"
          >
            Ver todas as atividades ({activities?.length - showLimit} mais)
          </Button>
        </div>
      )}
    </div>
  );
};

export default ActivityFeed;