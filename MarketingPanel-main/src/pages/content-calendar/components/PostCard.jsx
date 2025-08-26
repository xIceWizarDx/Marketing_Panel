import React from 'react';
import Icon from '../../../components/AppIcon';
import Image from '../../../components/AppImage';

const PostCard = ({ post, onClick, viewMode = 'month' }) => {
  const getPlatformIcon = (platform) => {
    const icons = {
      'instagram': 'Instagram',
      'facebook': 'Facebook',
      'youtube': 'Youtube',
      'tiktok': 'Music',
      'twitter': 'Twitter',
      'google-ads': 'Search'
    };
    return icons?.[platform] || 'Globe';
  };

  const getPlatformColor = (platform) => {
    const colors = {
      'instagram': 'bg-pink-500',
      'facebook': 'bg-blue-600',
      'youtube': 'bg-red-600',
      'tiktok': 'bg-black',
      'twitter': 'bg-blue-400',
      'google-ads': 'bg-green-600'
    };
    return colors?.[platform] || 'bg-gray-500';
  };

  const getStatusColor = (status) => {
    const colors = {
      'published': 'text-success',
      'scheduled': 'text-accent',
      'failed': 'text-error',
      'draft': 'text-muted-foreground'
    };
    return colors?.[status] || 'text-muted-foreground';
  };

  const getStatusIcon = (status) => {
    const icons = {
      'published': 'CheckCircle',
      'scheduled': 'Clock',
      'failed': 'XCircle',
      'draft': 'Edit'
    };
    return icons?.[status] || 'Circle';
  };

  const formatTime = (date) => {
    return new Date(date)?.toLocaleTimeString('pt-BR', {
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'America/Sao_Paulo'
    });
  };

  const isCompactView = viewMode === 'month';

  return (
    <div
      onClick={() => onClick(post)}
      className={`bg-card border border-border rounded-lg p-3 cursor-pointer transition-smooth hover:shadow-soft hover:border-accent/50 ${
        isCompactView ? 'min-h-[80px]' : 'min-h-[120px]'
      }`}
    >
      {/* Header */}
      <div className="flex items-start justify-between mb-2">
        <div className="flex items-center gap-2">
          <div className={`w-4 h-4 rounded-full flex items-center justify-center ${getPlatformColor(post?.platform)}`}>
            <Icon 
              name={getPlatformIcon(post?.platform)} 
              size={10} 
              color="white" 
            />
          </div>
          
          <span className="text-xs font-body font-medium text-muted-foreground">
            {formatTime(post?.scheduledTime)}
          </span>
        </div>

        <div className={`flex items-center gap-1 ${getStatusColor(post?.status)}`}>
          <Icon name={getStatusIcon(post?.status)} size={12} />
          {!isCompactView && (
            <span className="text-xs font-body">
              {post?.status === 'published' ? 'Publicado' :
               post?.status === 'scheduled' ? 'Agendado' :
               post?.status === 'failed' ? 'Falhou' : 'Rascunho'}
            </span>
          )}
        </div>
      </div>
      {/* Content */}
      <div className="flex gap-2">
        {post?.media && post?.media?.length > 0 && (
          <div className="flex-shrink-0">
            <div className={`rounded overflow-hidden ${isCompactView ? 'w-8 h-8' : 'w-12 h-12'}`}>
              <Image
                src={post?.media?.[0]?.url}
                alt="Post media"
                className="w-full h-full object-cover"
              />
            </div>
          </div>
        )}

        <div className="flex-1 min-w-0">
          <p className={`text-foreground font-body line-clamp-2 ${
            isCompactView ? 'text-xs' : 'text-sm'
          }`}>
            {post?.caption}
          </p>
          
          {!isCompactView && post?.hashtags && post?.hashtags?.length > 0 && (
            <div className="flex flex-wrap gap-1 mt-1">
              {post?.hashtags?.slice(0, 3)?.map((tag, index) => (
                <span key={index} className="text-xs text-accent font-body">
                  #{tag}
                </span>
              ))}
              {post?.hashtags?.length > 3 && (
                <span className="text-xs text-muted-foreground font-body">
                  +{post?.hashtags?.length - 3}
                </span>
              )}
            </div>
          )}
        </div>
      </div>
      {/* Multiple Platforms Indicator */}
      {post?.platforms && post?.platforms?.length > 1 && (
        <div className="flex items-center gap-1 mt-2 pt-2 border-t border-border">
          <Icon name="Share2" size={10} className="text-muted-foreground" />
          <span className="text-xs text-muted-foreground font-body">
            {post?.platforms?.length} plataformas
          </span>
        </div>
      )}
    </div>
  );
};

export default PostCard;