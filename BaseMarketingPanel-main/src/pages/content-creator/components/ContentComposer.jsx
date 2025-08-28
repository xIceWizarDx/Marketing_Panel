import React, { useState, useEffect } from 'react';
import Icon from '../../../components/AppIcon';

import Button from '../../../components/ui/Button';

const ContentComposer = ({ content, onContentChange, selectedPlatforms }) => {
  const [activeTab, setActiveTab] = useState('caption');
  const [hashtagSuggestions] = useState([
    '#marketing', '#socialmedia', '#business', '#empreendedorismo', 
    '#vendas', '#digital', '#sucesso', '#motivacao', '#inovacao', 
    '#estrategia', '#crescimento', '#networking', '#lideranca', 
    '#produtividade', '#resultados'
  ]);

  const platformLimits = {
    instagram: { caption: 2200, hashtags: 30 },
    facebook: { caption: 63206, hashtags: 0 },
    youtube: { caption: 5000, hashtags: 0 },
    tiktok: { caption: 300, hashtags: 100 },
    twitter: { caption: 280, hashtags: 0 },
    google_ads: { caption: 90, hashtags: 0 }
  };

  const getCharacterLimit = (field) => {
    if (selectedPlatforms?.length === 0) return 2200;
    
    const limits = selectedPlatforms?.map(platform => 
      platformLimits?.[platform]?.[field] || 2200
    );
    
    return Math.min(...limits);
  };

  const getCharacterCount = (text) => {
    return text ? text?.length : 0;
  };

  const getCharacterStatus = (text, field) => {
    const count = getCharacterCount(text);
    const limit = getCharacterLimit(field);
    const percentage = (count / limit) * 100;

    if (percentage >= 100) return 'error';
    if (percentage >= 80) return 'warning';
    return 'success';
  };

  const addHashtag = (hashtag) => {
    const currentHashtags = content?.hashtags || '';
    const hashtagsArray = currentHashtags?.split(' ')?.filter(tag => tag?.trim());
    
    if (!hashtagsArray?.includes(hashtag)) {
      const newHashtags = [...hashtagsArray, hashtag]?.join(' ');
      onContentChange('hashtags', newHashtags);
    }
  };

  const generatePlatformPreview = (platform) => {
    const caption = content?.caption || '';
    const hashtags = content?.hashtags || '';
    const fullText = `${caption}${hashtags ? `\n\n${hashtags}` : ''}`;

    switch (platform) {
      case 'instagram':
        return fullText?.substring(0, platformLimits?.instagram?.caption);
      case 'facebook':
        return fullText?.substring(0, platformLimits?.facebook?.caption);
      case 'youtube':
        return `${caption?.substring(0, platformLimits?.youtube?.caption)}`;
      case 'tiktok':
        return fullText?.substring(0, platformLimits?.tiktok?.caption);
      case 'twitter':
        return fullText?.substring(0, platformLimits?.twitter?.caption);
      case 'google_ads':
        return caption?.substring(0, platformLimits?.google_ads?.caption);
      default:
        return fullText;
    }
  };

  const tabs = [
    { id: 'caption', label: 'Legenda', icon: 'Type' },
    { id: 'hashtags', label: 'Hashtags', icon: 'Hash' },
    { id: 'preview', label: 'Visualizar', icon: 'Eye' }
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-body font-bold text-foreground">
          Criar Conteúdo
        </h3>
        <div className="flex items-center gap-2">
          {selectedPlatforms?.map(platform => (
            <div key={platform} className="w-6 h-6 bg-accent rounded-full flex items-center justify-center">
              <span className="text-xs text-accent-foreground font-bold">
                {platform?.charAt(0)?.toUpperCase()}
              </span>
            </div>
          ))}
        </div>
      </div>
      {/* Tab Navigation */}
      <div className="border-b border-border">
        <nav className="flex space-x-8">
          {tabs?.map((tab) => (
            <button
              key={tab?.id}
              onClick={() => setActiveTab(tab?.id)}
              className={`flex items-center gap-2 py-2 px-1 border-b-2 font-body font-medium text-sm transition-smooth ${
                activeTab === tab?.id
                  ? 'border-accent text-accent' :'border-transparent text-muted-foreground hover:text-foreground'
              }`}
            >
              <Icon name={tab?.icon} size={16} />
              {tab?.label}
            </button>
          ))}
        </nav>
      </div>
      {/* Tab Content */}
      <div className="min-h-[400px]">
        {activeTab === 'caption' && (
          <div className="space-y-4">
            <div className="relative">
              <textarea
                value={content?.caption || ''}
                onChange={(e) => onContentChange('caption', e?.target?.value)}
                placeholder="Escreva sua legenda aqui..."
                className="w-full h-64 p-4 border border-border rounded-lg resize-none focus:ring-2 focus:ring-accent focus:border-transparent font-body text-sm"
              />
              
              <div className={`absolute bottom-3 right-3 text-xs font-body ${
                getCharacterStatus(content?.caption, 'caption') === 'error' ? 'text-error' :
                getCharacterStatus(content?.caption, 'caption') === 'warning' ? 'text-warning' :
                'text-muted-foreground'
              }`}>
                {getCharacterCount(content?.caption)}/{getCharacterLimit('caption')}
              </div>
            </div>

            <div className="bg-accent/5 border border-accent/20 rounded-lg p-4">
              <div className="flex items-start gap-3">
                <Icon name="Lightbulb" size={20} className="text-accent flex-shrink-0 mt-0.5" />
                <div>
                  <h4 className="text-sm font-body font-medium text-foreground mb-2">
                    Dicas para uma boa legenda
                  </h4>
                  <ul className="text-xs text-muted-foreground font-body space-y-1">
                    <li>• Comece com uma pergunta ou frase impactante</li>
                    <li>• Use emojis para tornar o texto mais atrativo</li>
                    <li>• Inclua uma chamada para ação (CTA)</li>
                    <li>• Conte uma história ou compartilhe uma experiência</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'hashtags' && (
          <div className="space-y-4">
            <div className="relative">
              <textarea
                value={content?.hashtags || ''}
                onChange={(e) => onContentChange('hashtags', e?.target?.value)}
                placeholder="Adicione suas hashtags aqui... #exemplo #marketing"
                className="w-full h-32 p-4 border border-border rounded-lg resize-none focus:ring-2 focus:ring-accent focus:border-transparent font-body text-sm"
              />
              
              <div className="absolute bottom-3 right-3 text-xs text-muted-foreground font-body">
                {(content?.hashtags || '')?.split(' ')?.filter(tag => tag?.trim()?.startsWith('#'))?.length} hashtags
              </div>
            </div>

            <div>
              <h4 className="text-sm font-body font-medium text-foreground mb-3">
                Sugestões de Hashtags
              </h4>
              <div className="flex flex-wrap gap-2">
                {hashtagSuggestions?.map((hashtag) => (
                  <Button
                    key={hashtag}
                    variant="outline"
                    size="sm"
                    onClick={() => addHashtag(hashtag)}
                    className="text-xs"
                  >
                    {hashtag}
                  </Button>
                ))}
              </div>
            </div>

            <div className="bg-warning/5 border border-warning/20 rounded-lg p-4">
              <div className="flex items-start gap-3">
                <Icon name="AlertTriangle" size={20} className="text-warning flex-shrink-0 mt-0.5" />
                <div>
                  <h4 className="text-sm font-body font-medium text-foreground mb-2">
                    Limites por Plataforma
                  </h4>
                  <ul className="text-xs text-muted-foreground font-body space-y-1">
                    <li>• Instagram: Máximo 30 hashtags por post</li>
                    <li>• TikTok: Máximo 100 caracteres para hashtags</li>
                    <li>• Facebook: Hashtags não são recomendadas</li>
                    <li>• X (Twitter): Incluídas no limite de 280 caracteres</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'preview' && (
          <div className="space-y-6">
            <h4 className="text-base font-body font-medium text-foreground">
              Visualização por Plataforma
            </h4>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {selectedPlatforms?.map((platform) => (
                <div key={platform} className="bg-card border border-border rounded-lg p-4">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-8 h-8 bg-accent rounded-lg flex items-center justify-center">
                      <span className="text-xs text-accent-foreground font-bold">
                        {platform?.charAt(0)?.toUpperCase()}
                      </span>
                    </div>
                    <h5 className="font-body font-medium text-sm text-foreground capitalize">
                      {platform === 'twitter' ? 'X (Twitter)' : platform}
                    </h5>
                  </div>

                  <div className="bg-muted/30 rounded-lg p-3 min-h-[120px]">
                    <p className="text-sm font-body text-foreground whitespace-pre-wrap">
                      {generatePlatformPreview(platform) || 'Sua legenda aparecerá aqui...'}
                    </p>
                  </div>

                  <div className="mt-3 text-xs text-muted-foreground font-body">
                    {getCharacterCount(generatePlatformPreview(platform))}/{platformLimits?.[platform]?.caption || 'Sem limite'} caracteres
                  </div>
                </div>
              ))}
            </div>

            {selectedPlatforms?.length === 0 && (
              <div className="text-center py-12">
                <Icon name="Eye" size={48} className="text-muted-foreground mx-auto mb-3" />
                <p className="text-sm text-muted-foreground font-body">
                  Selecione plataformas para visualizar o conteúdo
                </p>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default ContentComposer;