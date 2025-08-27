import React, { useState, useEffect } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import Input from '../../../components/ui/Input';
import Image from '../../../components/AppImage';

const PostEditDrawer = ({ post, isOpen, onClose, onSave, onDelete }) => {
  const [editedPost, setEditedPost] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (post) {
      setEditedPost({
        ...post,
        scheduledDate: new Date(post.scheduledTime)?.toISOString()?.split('T')?.[0],
        scheduledTime: new Date(post.scheduledTime)?.toTimeString()?.slice(0, 5)
      });
    }
  }, [post]);

  const handleSave = async () => {
    if (!editedPost) return;
    
    setIsLoading(true);
    
    // Simulate API call
    setTimeout(() => {
      const updatedPost = {
        ...editedPost,
        scheduledTime: new Date(`${editedPost.scheduledDate}T${editedPost.scheduledTime}:00`)?.toISOString()
      };
      
      onSave(updatedPost);
      setIsLoading(false);
      onClose();
    }, 1000);
  };

  const handleDelete = async () => {
    if (!post) return;
    
    setIsLoading(true);
    
    // Simulate API call
    setTimeout(() => {
      onDelete(post?.id);
      setIsLoading(false);
      onClose();
    }, 500);
  };

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

  const getStatusLabel = (status) => {
    const labels = {
      'published': 'Publicado',
      'scheduled': 'Agendado',
      'failed': 'Falhou',
      'draft': 'Rascunho'
    };
    return labels?.[status] || status;
  };

  if (!isOpen || !post || !editedPost) return null;

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/50 z-40 transition-drawer"
        onClick={onClose}
        aria-hidden="true"
      />
      {/* Drawer */}
      <div className="fixed right-0 top-0 h-full w-full max-w-md bg-card border-l border-border z-50 transition-drawer transform translate-x-0 overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-border sticky top-0 bg-card">
          <div className="flex items-center gap-3">
            <div className={`w-8 h-8 rounded-full flex items-center justify-center ${getPlatformColor(post?.platform)}`}>
              <Icon 
                name={getPlatformIcon(post?.platform)} 
                size={16} 
                color="white" 
              />
            </div>
            <div>
              <h2 className="font-body font-bold text-lg text-primary">
                Editar Post
              </h2>
              <p className="text-xs text-muted-foreground font-body">
                {getStatusLabel(post?.status)}
              </p>
            </div>
          </div>
          
          <Button
            variant="ghost"
            size="icon"
            onClick={onClose}
            aria-label="Fechar"
          >
            <Icon name="X" size={20} />
          </Button>
        </div>

        {/* Content */}
        <div className="p-4 space-y-6">
          {/* Media Preview */}
          {post?.media && post?.media?.length > 0 && (
            <div>
              <label className="block text-sm font-body font-medium text-foreground mb-2">
                Mídia
              </label>
              <div className="grid grid-cols-2 gap-2">
                {post?.media?.map((media, index) => (
                  <div key={index} className="aspect-square rounded-lg overflow-hidden">
                    <Image
                      src={media?.url}
                      alt={`Media ${index + 1}`}
                      className="w-full h-full object-cover"
                    />
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Caption */}
          <div>
            <label className="block text-sm font-body font-medium text-foreground mb-2">
              Legenda
            </label>
            <textarea
              value={editedPost?.caption}
              onChange={(e) => setEditedPost({ ...editedPost, caption: e?.target?.value })}
              className="w-full min-h-[100px] p-3 border border-border rounded-lg bg-input text-foreground font-body text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder="Escreva sua legenda..."
            />
            <p className="text-xs text-muted-foreground mt-1 font-body">
              {editedPost?.caption?.length} caracteres
            </p>
          </div>

          {/* Hashtags */}
          {editedPost?.hashtags && editedPost?.hashtags?.length > 0 && (
            <div>
              <label className="block text-sm font-body font-medium text-foreground mb-2">
                Hashtags
              </label>
              <div className="flex flex-wrap gap-2">
                {editedPost?.hashtags?.map((tag, index) => (
                  <span key={index} className="bg-accent/10 text-accent px-2 py-1 rounded text-sm font-body">
                    #{tag}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Scheduling */}
          <div className="space-y-4">
            <h3 className="text-sm font-body font-medium text-foreground">
              Agendamento
            </h3>
            
            <div className="grid grid-cols-2 gap-3">
              <Input
                label="Data"
                type="date"
                value={editedPost?.scheduledDate}
                onChange={(e) => setEditedPost({ ...editedPost, scheduledDate: e?.target?.value })}
              />
              
              <Input
                label="Horário"
                type="time"
                value={editedPost?.scheduledTime}
                onChange={(e) => setEditedPost({ ...editedPost, scheduledTime: e?.target?.value })}
              />
            </div>
            
            <p className="text-xs text-muted-foreground font-body">
              Fuso horário: America/São_Paulo
            </p>
          </div>

          {/* Platform Info */}
          <div>
            <label className="block text-sm font-body font-medium text-foreground mb-2">
              Plataforma
            </label>
            <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
              <div className={`w-6 h-6 rounded-full flex items-center justify-center ${getPlatformColor(post?.platform)}`}>
                <Icon 
                  name={getPlatformIcon(post?.platform)} 
                  size={12} 
                  color="white" 
                />
              </div>
              <span className="font-body text-sm text-foreground capitalize">
                {post?.platform?.replace('-', ' ')}
              </span>
            </div>
          </div>

          {/* Actions */}
          <div className="space-y-3 pt-4 border-t border-border">
            <Button
              variant="default"
              fullWidth
              onClick={handleSave}
              loading={isLoading}
              iconName="Save"
              iconPosition="left"
            >
              Salvar Alterações
            </Button>
            
            <div className="grid grid-cols-2 gap-3">
              <Button
                variant="outline"
                onClick={() => {
                  // Duplicate post logic
                  console.log('Duplicating post:', post?.id);
                }}
                iconName="Copy"
                iconPosition="left"
              >
                Duplicar
              </Button>
              
              <Button
                variant="destructive"
                onClick={handleDelete}
                loading={isLoading}
                iconName="Trash2"
                iconPosition="left"
              >
                Excluir
              </Button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default PostEditDrawer;