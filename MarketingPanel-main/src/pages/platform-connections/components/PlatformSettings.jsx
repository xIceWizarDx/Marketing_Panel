import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import Input from '../../../components/ui/Input';
import { Checkbox } from '../../../components/ui/Checkbox';
import Select from '../../../components/ui/Select';

const PlatformSettings = ({ platform, isOpen, onClose, onSave }) => {
  const [settings, setSettings] = useState({
    autoPost: platform?.settings?.autoPost || false,
    defaultHashtags: platform?.settings?.defaultHashtags || '',
    postFormat: platform?.settings?.postFormat || 'standard',
    scheduleTime: platform?.settings?.scheduleTime || '09:00',
    enableAnalytics: platform?.settings?.enableAnalytics || true,
    contentApproval: platform?.settings?.contentApproval || false,
    maxHashtags: platform?.settings?.maxHashtags || 10,
    imageQuality: platform?.settings?.imageQuality || 'high'
  });

  const formatOptions = [
    { value: 'standard', label: 'Padrão' },
    { value: 'story', label: 'Stories' },
    { value: 'reel', label: 'Reels/Vídeos Curtos' },
    { value: 'carousel', label: 'Carrossel' }
  ];

  const qualityOptions = [
    { value: 'high', label: 'Alta Qualidade' },
    { value: 'medium', label: 'Qualidade Média' },
    { value: 'low', label: 'Baixa Qualidade' }
  ];

  const handleSettingChange = (key, value) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  const handleSave = () => {
    onSave(platform?.id, settings);
    onClose();
  };

  if (!isOpen || !platform) return null;

  return (
    <>
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black/50 z-50 transition-drawer"
        onClick={onClose}
      />
      {/* Modal */}
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div className="bg-card border border-border rounded-lg shadow-modal w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-border">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-muted rounded-lg flex items-center justify-center">
                <Icon name="Settings" size={20} className="text-muted-foreground" />
              </div>
              <div>
                <h2 className="font-body font-semibold text-lg text-card-foreground">
                  Configurações - {platform?.name}
                </h2>
                <p className="text-sm text-muted-foreground">
                  Personalize as configurações da plataforma
                </p>
              </div>
            </div>
            <Button
              variant="ghost"
              size="icon"
              onClick={onClose}
            >
              <Icon name="X" size={20} />
            </Button>
          </div>

          {/* Content */}
          <div className="p-6 space-y-6">
            {/* General Settings */}
            <div>
              <h3 className="font-body font-medium text-base text-card-foreground mb-4">
                Configurações Gerais
              </h3>
              <div className="space-y-4">
                <Checkbox
                  label="Publicação Automática"
                  description="Permitir publicações automáticas agendadas"
                  checked={settings?.autoPost}
                  onChange={(e) => handleSettingChange('autoPost', e?.target?.checked)}
                />
                
                <Checkbox
                  label="Habilitar Analytics"
                  description="Coletar dados de engajamento e métricas"
                  checked={settings?.enableAnalytics}
                  onChange={(e) => handleSettingChange('enableAnalytics', e?.target?.checked)}
                />
                
                <Checkbox
                  label="Aprovação de Conteúdo"
                  description="Requerer aprovação antes da publicação"
                  checked={settings?.contentApproval}
                  onChange={(e) => handleSettingChange('contentApproval', e?.target?.checked)}
                />
              </div>
            </div>

            {/* Content Settings */}
            <div>
              <h3 className="font-body font-medium text-base text-card-foreground mb-4">
                Configurações de Conteúdo
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Select
                  label="Formato Padrão"
                  options={formatOptions}
                  value={settings?.postFormat}
                  onChange={(value) => handleSettingChange('postFormat', value)}
                />
                
                <Select
                  label="Qualidade da Imagem"
                  options={qualityOptions}
                  value={settings?.imageQuality}
                  onChange={(value) => handleSettingChange('imageQuality', value)}
                />
                
                <Input
                  label="Horário Padrão"
                  type="time"
                  value={settings?.scheduleTime}
                  onChange={(e) => handleSettingChange('scheduleTime', e?.target?.value)}
                />
                
                <Input
                  label="Máximo de Hashtags"
                  type="number"
                  min="1"
                  max="30"
                  value={settings?.maxHashtags}
                  onChange={(e) => handleSettingChange('maxHashtags', parseInt(e?.target?.value))}
                />
              </div>
            </div>

            {/* Hashtags Settings */}
            <div>
              <h3 className="font-body font-medium text-base text-card-foreground mb-4">
                Hashtags Padrão
              </h3>
              <Input
                label="Hashtags"
                type="text"
                placeholder="#marketing #socialmedia #content"
                description="Hashtags que serão adicionadas automaticamente aos posts"
                value={settings?.defaultHashtags}
                onChange={(e) => handleSettingChange('defaultHashtags', e?.target?.value)}
              />
            </div>

            {/* Platform Specific Settings */}
            {platform?.id === 'instagram' && (
              <div>
                <h3 className="font-body font-medium text-base text-card-foreground mb-4">
                  Configurações do Instagram
                </h3>
                <div className="space-y-4">
                  <Checkbox
                    label="Publicar no Feed e Stories"
                    description="Publicar automaticamente no feed e stories"
                    checked={settings?.crossPost || false}
                    onChange={(e) => handleSettingChange('crossPost', e?.target?.checked)}
                  />
                  
                  <Checkbox
                    label="Adicionar Localização"
                    description="Incluir localização nos posts quando disponível"
                    checked={settings?.addLocation || false}
                    onChange={(e) => handleSettingChange('addLocation', e?.target?.checked)}
                  />
                </div>
              </div>
            )}

            {platform?.id === 'youtube' && (
              <div>
                <h3 className="font-body font-medium text-base text-card-foreground mb-4">
                  Configurações do YouTube
                </h3>
                <div className="space-y-4">
                  <Select
                    label="Privacidade Padrão"
                    options={[
                      { value: 'public', label: 'Público' },
                      { value: 'unlisted', label: 'Não Listado' },
                      { value: 'private', label: 'Privado' }
                    ]}
                    value={settings?.defaultPrivacy || 'public'}
                    onChange={(value) => handleSettingChange('defaultPrivacy', value)}
                  />
                  
                  <Checkbox
                    label="Monetização Automática"
                    description="Habilitar monetização para novos vídeos"
                    checked={settings?.autoMonetize || false}
                    onChange={(e) => handleSettingChange('autoMonetize', e?.target?.checked)}
                  />
                </div>
              </div>
            )}
          </div>

          {/* Actions */}
          <div className="flex flex-col sm:flex-row gap-3 p-6 border-t border-border">
            <Button
              variant="outline"
              onClick={onClose}
              className="flex-1"
            >
              Cancelar
            </Button>
            <Button
              variant="default"
              onClick={handleSave}
              iconName="Save"
              iconPosition="left"
              className="flex-1"
            >
              Salvar Configurações
            </Button>
          </div>
        </div>
      </div>
    </>
  );
};

export default PlatformSettings;