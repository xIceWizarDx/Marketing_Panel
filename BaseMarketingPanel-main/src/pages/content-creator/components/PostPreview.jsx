import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import Image from '../../../components/AppImage';

const PostPreview = ({ 
  selectedPlatforms, 
  uploadedMedia, 
  content, 
  schedulingData, 
  onPublish, 
  onSchedule 
}) => {
  const [activePreview, setActivePreview] = useState(selectedPlatforms?.[0] || 'instagram');

  const platformMockups = {
    instagram: {
      name: 'Instagram',
      icon: 'Instagram',
      color: 'bg-gradient-to-r from-purple-500 to-pink-500',
      username: '@minha_empresa',
      avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
      followers: '12.5K'
    },
    facebook: {
      name: 'Facebook',
      icon: 'Facebook',
      color: 'bg-blue-600',
      username: 'Minha Empresa',
      avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
      followers: '8.2K'
    },
    youtube: {
      name: 'YouTube',
      icon: 'Youtube',
      color: 'bg-red-600',
      username: 'Minha Empresa Canal',
      avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
      followers: '3.1K'
    },
    tiktok: {
      name: 'TikTok',
      icon: 'Music',
      color: 'bg-black',
      username: '@minhaempresa',
      avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
      followers: '15.8K'
    },
    twitter: {
      name: 'X (Twitter)',
      icon: 'Twitter',
      color: 'bg-black',
      username: '@minha_empresa',
      avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
      followers: '5.4K'
    }
  };

  const formatContent = (platform) => {
    const caption = content?.caption || '';
    const hashtags = content?.hashtags || '';
    
    switch (platform) {
      case 'instagram':
        return `${caption}${hashtags ? `\n\n${hashtags}` : ''}`;
      case 'facebook':
        return caption;
      case 'youtube':
        return caption;
      case 'tiktok':
        return `${caption}${hashtags ? ` ${hashtags}` : ''}`;
      case 'twitter':
        return `${caption}${hashtags ? ` ${hashtags}` : ''}`;
      default:
        return caption;
    }
  };

  const getSchedulingText = () => {
    if (schedulingData?.publishType === 'now') {
      return 'Publicação imediata';
    }
    
    if (schedulingData?.scheduledDate && schedulingData?.scheduledTime) {
      const date = new Date(schedulingData.scheduledDate + 'T' + schedulingData.scheduledTime);
      return `Agendado para ${date?.toLocaleDateString('pt-BR')} às ${schedulingData?.scheduledTime}`;
    }
    
    return 'Agendamento não configurado';
  };

  const canPublish = () => {
    return selectedPlatforms?.length > 0 && 
           (content?.caption || uploadedMedia?.length > 0) &&
           (schedulingData?.publishType === 'now' || 
            (schedulingData?.scheduledDate && schedulingData?.scheduledTime));
  };

  const handlePublishClick = () => {
    if (schedulingData?.publishType === 'now') {
      onPublish();
    } else {
      onSchedule();
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-body font-bold text-foreground">
          Visualização Final
        </h3>
        <div className="flex items-center gap-2">
          <Icon name="Eye" size={16} className="text-muted-foreground" />
          <span className="text-sm text-muted-foreground font-body">
            {selectedPlatforms?.length} plataforma(s)
          </span>
        </div>
      </div>
      {selectedPlatforms?.length > 0 ? (
        <>
          {/* Platform Tabs */}
          <div className="flex flex-wrap gap-2">
            {selectedPlatforms?.map((platform) => (
              <Button
                key={platform}
                variant={activePreview === platform ? 'default' : 'outline'}
                size="sm"
                onClick={() => setActivePreview(platform)}
                className="flex items-center gap-2"
              >
                <div className={`w-4 h-4 rounded ${platformMockups?.[platform]?.color}`}>
                  <Icon name={platformMockups?.[platform]?.icon} size={12} color="white" />
                </div>
                {platformMockups?.[platform]?.name}
              </Button>
            ))}
          </div>

          {/* Preview Content */}
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            {/* Platform Header */}
            <div className="p-4 border-b border-border">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full overflow-hidden">
                  <Image
                    src={platformMockups?.[activePreview]?.avatar}
                    alt="Profile"
                    className="w-full h-full object-cover"
                  />
                </div>
                <div>
                  <h4 className="font-body font-medium text-sm text-foreground">
                    {platformMockups?.[activePreview]?.username}
                  </h4>
                  <p className="text-xs text-muted-foreground font-body">
                    {platformMockups?.[activePreview]?.followers} seguidores
                  </p>
                </div>
                <div className="ml-auto">
                  <Icon name="MoreHorizontal" size={20} className="text-muted-foreground" />
                </div>
              </div>
            </div>

            {/* Media Content */}
            {uploadedMedia?.length > 0 && (
              <div className="aspect-square bg-muted relative overflow-hidden">
                {uploadedMedia?.length === 1 ? (
                  <Image
                    src={uploadedMedia?.[0]?.url}
                    alt="Post media"
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <div className="grid grid-cols-2 gap-1 h-full">
                    {uploadedMedia?.slice(0, 4)?.map((media, index) => (
                      <div key={media?.id} className="relative overflow-hidden">
                        <Image
                          src={media?.url}
                          alt={`Media ${index + 1}`}
                          className="w-full h-full object-cover"
                        />
                        {index === 3 && uploadedMedia?.length > 4 && (
                          <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
                            <span className="text-white font-body font-bold text-lg">
                              +{uploadedMedia?.length - 4}
                            </span>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Post Content */}
            <div className="p-4">
              {/* Engagement Actions */}
              <div className="flex items-center gap-4 mb-3">
                <Icon name="Heart" size={24} className="text-muted-foreground" />
                <Icon name="MessageCircle" size={24} className="text-muted-foreground" />
                <Icon name="Send" size={24} className="text-muted-foreground" />
                <Icon name="Bookmark" size={24} className="text-muted-foreground ml-auto" />
              </div>

              {/* Caption */}
              {formatContent(activePreview) && (
                <div className="mb-3">
                  <p className="text-sm font-body text-foreground whitespace-pre-wrap">
                    <span className="font-medium">{platformMockups?.[activePreview]?.username}</span>{' '}
                    {formatContent(activePreview)}
                  </p>
                </div>
              )}

              {/* Timestamp */}
              <p className="text-xs text-muted-foreground font-body">
                {schedulingData?.publishType === 'now' ? 'Agora' : getSchedulingText()}
              </p>
            </div>
          </div>

          {/* Publishing Summary */}
          <div className="bg-muted/30 rounded-lg p-4">
            <h4 className="text-sm font-body font-medium text-foreground mb-3">
              Resumo da Publicação
            </h4>
            
            <div className="space-y-2 text-xs font-body">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Plataformas:</span>
                <span className="text-foreground">{selectedPlatforms?.length}</span>
              </div>
              
              <div className="flex justify-between">
                <span className="text-muted-foreground">Mídia:</span>
                <span className="text-foreground">{uploadedMedia?.length} arquivo(s)</span>
              </div>
              
              <div className="flex justify-between">
                <span className="text-muted-foreground">Caracteres:</span>
                <span className="text-foreground">
                  {(content?.caption || '')?.length + (content?.hashtags || '')?.length}
                </span>
              </div>
              
              <div className="flex justify-between">
                <span className="text-muted-foreground">Agendamento:</span>
                <span className="text-foreground">{getSchedulingText()}</span>
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex flex-col sm:flex-row gap-3">
            <Button
              variant="outline"
              size="lg"
              iconName="Save"
              iconPosition="left"
              className="flex-1"
            >
              Salvar Rascunho
            </Button>
            
            <Button
              variant="default"
              size="lg"
              onClick={handlePublishClick}
              disabled={!canPublish()}
              iconName={schedulingData?.publishType === 'now' ? 'Send' : 'Calendar'}
              iconPosition="left"
              className="flex-1"
            >
              {schedulingData?.publishType === 'now' ? 'Publicar Agora' : 'Agendar Publicação'}
            </Button>
          </div>
        </>
      ) : (
        <div className="text-center py-12">
          <Icon name="Eye" size={48} className="text-muted-foreground mx-auto mb-3" />
          <p className="text-sm text-muted-foreground font-body mb-4">
            Selecione plataformas para visualizar a publicação
          </p>
          <Button variant="outline" size="sm">
            Voltar para Seleção
          </Button>
        </div>
      )}
    </div>
  );
};

export default PostPreview;