import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Image from '../../../components/AppImage';
import Button from '../../../components/ui/Button';
import { useToast } from '../../../components/ui/ToastNotificationSystem';

const PlatformCard = ({ platform, onConnect, onDisconnect, onShowSettings }) => {
  const [isConnecting, setIsConnecting] = useState(false);
  const { success, error } = useToast();

  const handleConnect = async () => {
    setIsConnecting(true);
    try {
      await new Promise(resolve => setTimeout(resolve, 2000)); // Simulate API call
      onConnect(platform?.id);
      success(`${platform?.name} conectado com sucesso!`);
    } catch (err) {
      error(`Erro ao conectar ${platform?.name}. Tente novamente.`);
    } finally {
      setIsConnecting(false);
    }
  };

  const handleDisconnect = () => {
    onDisconnect(platform?.id);
    success(`${platform?.name} desconectado com sucesso!`);
  };

  const getStatusBadge = () => {
    if (platform?.isConnected) {
      return (
        <div className="flex items-center gap-2 px-3 py-1 bg-success/10 border border-success/20 rounded-full">
          <div className="w-2 h-2 bg-success rounded-full"></div>
          <span className="text-xs font-medium text-success">Conectado</span>
        </div>
      );
    }
    return (
      <div className="flex items-center gap-2 px-3 py-1 bg-muted border border-border rounded-full">
        <div className="w-2 h-2 bg-muted-foreground rounded-full"></div>
        <span className="text-xs font-medium text-muted-foreground">Desconectado</span>
      </div>
    );
  };

  return (
    <div className="bg-card border border-border rounded-lg p-6 hover:shadow-modal transition-smooth">
      {/* Header */}
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 bg-muted rounded-lg flex items-center justify-center overflow-hidden">
            <Image 
              src={platform?.logo} 
              alt={`${platform?.name} logo`}
              className="w-8 h-8 object-contain"
            />
          </div>
          <div>
            <h3 className="font-body font-semibold text-lg text-card-foreground">
              {platform?.name}
            </h3>
            <p className="text-sm text-muted-foreground font-body">
              {platform?.description}
            </p>
          </div>
        </div>
        {getStatusBadge()}
      </div>
      {/* Connection Details */}
      {platform?.isConnected && platform?.accountInfo && (
        <div className="mb-4 p-4 bg-muted/30 rounded-lg">
          <div className="flex items-center gap-2 mb-2">
            <Icon name="User" size={16} className="text-muted-foreground" />
            <span className="text-sm font-medium text-card-foreground">
              {platform?.accountInfo?.username}
            </span>
          </div>
          <div className="flex items-center gap-2 mb-2">
            <Icon name="Mail" size={16} className="text-muted-foreground" />
            <span className="text-sm text-muted-foreground">
              {platform?.accountInfo?.email}
            </span>
          </div>
          <div className="flex items-center gap-2">
            <Icon name="Clock" size={16} className="text-muted-foreground" />
            <span className="text-xs text-muted-foreground">
              Última sincronização: {platform?.lastSync}
            </span>
          </div>
        </div>
      )}
      {/* Actions */}
      <div className="flex flex-col sm:flex-row gap-3">
        {platform?.isConnected ? (
          <>
            <Button
              variant="outline"
              iconName="Settings"
              iconPosition="left"
              onClick={() => onShowSettings(platform?.id)}
              className="flex-1"
            >
              Configurações
            </Button>
            <Button
              variant="destructive"
              iconName="Unlink"
              iconPosition="left"
              onClick={handleDisconnect}
            >
              Desconectar
            </Button>
          </>
        ) : (
          <Button
            variant="default"
            iconName="Link"
            iconPosition="left"
            loading={isConnecting}
            onClick={handleConnect}
            fullWidth
          >
            {isConnecting ? 'Conectando...' : 'Conectar'}
          </Button>
        )}
      </div>
      {/* Platform Stats */}
      {platform?.isConnected && platform?.stats && (
        <div className="mt-4 pt-4 border-t border-border">
          <div className="grid grid-cols-3 gap-4">
            <div className="text-center">
              <div className="text-lg font-semibold text-card-foreground">
                {platform?.stats?.posts}
              </div>
              <div className="text-xs text-muted-foreground">Posts</div>
            </div>
            <div className="text-center">
              <div className="text-lg font-semibold text-card-foreground">
                {platform?.stats?.followers}
              </div>
              <div className="text-xs text-muted-foreground">Seguidores</div>
            </div>
            <div className="text-center">
              <div className="text-lg font-semibold text-card-foreground">
                {platform?.stats?.engagement}
              </div>
              <div className="text-xs text-muted-foreground">Engajamento</div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PlatformCard;