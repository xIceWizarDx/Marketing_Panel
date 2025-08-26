import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Image from '../../../components/AppImage';
import Button from '../../../components/ui/Button';

const ProfileHeader = ({ user, onImageUpload }) => {
  const [isUploading, setIsUploading] = useState(false);

  const handleImageUpload = async (event) => {
    const file = event?.target?.files?.[0];
    if (!file) return;

    setIsUploading(true);
    // Simulate upload delay
    setTimeout(() => {
      onImageUpload(URL.createObjectURL(file));
      setIsUploading(false);
    }, 1500);
  };

  return (
    <div className="bg-card border border-border rounded-lg p-6 mb-6">
      <div className="flex flex-col sm:flex-row items-start sm:items-center gap-6">
        {/* Profile Image */}
        <div className="relative">
          <div className="w-24 h-24 rounded-full overflow-hidden bg-muted border-4 border-background shadow-soft">
            {user?.avatar ? (
              <Image
                src={user?.avatar}
                alt={`Foto de perfil de ${user?.name}`}
                className="w-full h-full object-cover"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center bg-accent">
                <Icon name="User" size={32} color="white" />
              </div>
            )}
          </div>
          
          {/* Upload Button */}
          <div className="absolute -bottom-2 -right-2">
            <label htmlFor="profile-image-upload" className="cursor-pointer">
              <div className="w-8 h-8 bg-accent hover:bg-accent/90 rounded-full flex items-center justify-center shadow-modal transition-smooth">
                {isUploading ? (
                  <Icon name="Loader2" size={16} color="white" className="animate-spin" />
                ) : (
                  <Icon name="Camera" size={16} color="white" />
                )}
              </div>
            </label>
            <input
              id="profile-image-upload"
              type="file"
              accept="image/*"
              onChange={handleImageUpload}
              className="hidden"
              disabled={isUploading}
            />
          </div>
        </div>

        {/* User Info */}
        <div className="flex-1 min-w-0">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 className="text-2xl font-body font-bold text-primary mb-1">
                {user?.name}
              </h1>
              <p className="text-muted-foreground font-body mb-2">
                {user?.email}
              </p>
              <div className="flex items-center gap-2">
                <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-body font-medium ${
                  user?.accountType === 'premium' ?'bg-accent/10 text-accent' :'bg-muted text-muted-foreground'
                }`}>
                  <Icon name={user?.accountType === 'premium' ? 'Crown' : 'User'} size={12} />
                  {user?.accountType === 'premium' ? 'Premium' : 'Gratuito'}
                </span>
                <span className="text-xs text-muted-foreground font-body">
                  Membro desde {user?.memberSince}
                </span>
              </div>
            </div>

            {/* Quick Actions */}
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" iconName="Download" iconPosition="left">
                Exportar Dados
              </Button>
              <Button variant="ghost" size="icon" aria-label="Mais opções">
                <Icon name="MoreVertical" size={16} />
              </Button>
            </div>
          </div>
        </div>
      </div>
      {/* Stats */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-border">
        <div className="text-center">
          <div className="text-xl font-body font-bold text-primary">
            {user?.stats?.postsCreated}
          </div>
          <div className="text-xs text-muted-foreground font-body">
            Posts Criados
          </div>
        </div>
        <div className="text-center">
          <div className="text-xl font-body font-bold text-primary">
            {user?.stats?.platformsConnected}
          </div>
          <div className="text-xs text-muted-foreground font-body">
            Plataformas
          </div>
        </div>
        <div className="text-center">
          <div className="text-xl font-body font-bold text-primary">
            {user?.stats?.scheduledPosts}
          </div>
          <div className="text-xs text-muted-foreground font-body">
            Agendados
          </div>
        </div>
        <div className="text-center">
          <div className="text-xl font-body font-bold text-primary">
            {user?.stats?.totalReach}
          </div>
          <div className="text-xs text-muted-foreground font-body">
            Alcance Total
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProfileHeader;