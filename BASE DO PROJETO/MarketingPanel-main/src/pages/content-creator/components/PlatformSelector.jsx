import React from 'react';
import Icon from '../../../components/AppIcon';
import { Checkbox } from '../../../components/ui/Checkbox';

const PlatformSelector = ({ selectedPlatforms, onPlatformToggle, connectedPlatforms }) => {
  const platforms = [
    {
      id: 'instagram',
      name: 'Instagram',
      icon: 'Instagram',
      color: 'bg-gradient-to-r from-purple-500 to-pink-500',
      followers: '12.5K',
      username: '@minha_empresa',
      isConnected: true
    },
    {
      id: 'facebook',
      name: 'Facebook',
      icon: 'Facebook',
      color: 'bg-blue-600',
      followers: '8.2K',
      username: 'Minha Empresa',
      isConnected: true
    },
    {
      id: 'youtube',
      name: 'YouTube',
      icon: 'Youtube',
      color: 'bg-red-600',
      followers: '3.1K',
      username: 'Minha Empresa Canal',
      isConnected: true
    },
    {
      id: 'tiktok',
      name: 'TikTok',
      icon: 'Music',
      color: 'bg-black',
      followers: '15.8K',
      username: '@minhaempresa',
      isConnected: true
    },
    {
      id: 'twitter',
      name: 'X (Twitter)',
      icon: 'Twitter',
      color: 'bg-black',
      followers: '5.4K',
      username: '@minha_empresa',
      isConnected: true
    },
    {
      id: 'google_ads',
      name: 'Google Ads',
      icon: 'Search',
      color: 'bg-green-600',
      followers: 'Ativo',
      username: 'Campanha Principal',
      isConnected: false
    }
  ];

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-body font-bold text-foreground">
          Selecionar Plataformas
        </h3>
        <span className="text-sm text-muted-foreground font-body">
          {selectedPlatforms?.length} selecionada(s)
        </span>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {platforms?.map((platform) => (
          <div
            key={platform?.id}
            className={`relative p-4 rounded-lg border-2 transition-smooth cursor-pointer ${
              selectedPlatforms?.includes(platform?.id)
                ? 'border-accent bg-accent/5' :'border-border bg-card hover:border-accent/50'
            } ${!platform?.isConnected ? 'opacity-60' : ''}`}
            onClick={() => platform?.isConnected && onPlatformToggle(platform?.id)}
          >
            <div className="flex items-start gap-3">
              <div className={`w-12 h-12 rounded-lg ${platform?.color} flex items-center justify-center flex-shrink-0`}>
                <Icon name={platform?.icon} size={24} color="white" />
              </div>

              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <h4 className="font-body font-medium text-sm text-foreground">
                    {platform?.name}
                  </h4>
                  {platform?.isConnected ? (
                    <div className="w-2 h-2 bg-success rounded-full" />
                  ) : (
                    <div className="w-2 h-2 bg-muted-foreground rounded-full" />
                  )}
                </div>

                <p className="text-xs text-muted-foreground font-body mb-1">
                  {platform?.username}
                </p>

                <p className="text-xs text-muted-foreground font-body">
                  {platform?.followers} {platform?.id !== 'google_ads' ? 'seguidores' : ''}
                </p>
              </div>

              {platform?.isConnected && (
                <Checkbox
                  checked={selectedPlatforms?.includes(platform?.id)}
                  onChange={() => onPlatformToggle(platform?.id)}
                  className="mt-1"
                />
              )}
            </div>

            {!platform?.isConnected && (
              <div className="absolute inset-0 bg-muted/20 rounded-lg flex items-center justify-center">
                <div className="text-center">
                  <Icon name="Link" size={20} className="text-muted-foreground mx-auto mb-1" />
                  <p className="text-xs text-muted-foreground font-body">
                    Não conectado
                  </p>
                </div>
              </div>
            )}
          </div>
        ))}
      </div>
      {selectedPlatforms?.length === 0 && (
        <div className="text-center py-8">
          <Icon name="CheckSquare" size={48} className="text-muted-foreground mx-auto mb-3" />
          <p className="text-sm text-muted-foreground font-body">
            Selecione pelo menos uma plataforma para continuar
          </p>
        </div>
      )}
    </div>
  );
};

export default PlatformSelector;