import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import { Checkbox } from '../../../components/ui/Checkbox';
import Select from '../../../components/ui/Select';
import Button from '../../../components/ui/Button';

const PreferencesSection = ({ preferences, onSave, isExpanded, onToggle }) => {
  const [formData, setFormData] = useState({
    theme: preferences?.theme || 'system',
    language: preferences?.language || 'pt-BR',
    timezone: preferences?.timezone || 'America/Sao_Paulo',
    emailNotifications: preferences?.emailNotifications || false,
    pushNotifications: preferences?.pushNotifications || true,
    marketingEmails: preferences?.marketingEmails || false,
    weeklyReports: preferences?.weeklyReports || true,
    platformAlerts: preferences?.platformAlerts || {
      facebook: true,
      instagram: true,
      youtube: false,
      tiktok: true,
      twitter: false
    }
  });
  const [isSaving, setIsSaving] = useState(false);

  const themeOptions = [
    { value: 'light', label: 'Claro' },
    { value: 'dark', label: 'Escuro' },
    { value: 'system', label: 'Sistema' }
  ];

  const languageOptions = [
    { value: 'pt-BR', label: 'Português (Brasil)' },
    { value: 'en-US', label: 'English (US)' },
    { value: 'es-ES', label: 'Español' }
  ];

  const timezoneOptions = [
    { value: 'America/Sao_Paulo', label: 'Brasília (GMT-3)' },
    { value: 'America/Manaus', label: 'Manaus (GMT-4)' },
    { value: 'America/Rio_Branco', label: 'Rio Branco (GMT-5)' }
  ];

  const handleInputChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handlePlatformAlertChange = (platform, checked) => {
    setFormData(prev => ({
      ...prev,
      platformAlerts: {
        ...prev?.platformAlerts,
        [platform]: checked
      }
    }));
  };

  const handleSave = async () => {
    setIsSaving(true);
    // Simulate API call
    setTimeout(() => {
      onSave(formData);
      setIsSaving(false);
    }, 1000);
  };

  return (
    <div className="bg-card border border-border rounded-lg mb-4">
      <button
        onClick={onToggle}
        className="w-full flex items-center justify-between p-6 text-left hover:bg-muted/50 transition-smooth"
        aria-expanded={isExpanded}
      >
        <div className="flex items-center gap-3">
          <Icon name="Settings" size={20} className="text-accent" />
          <div>
            <h3 className="font-body font-semibold text-lg text-primary">
              Preferências
            </h3>
            <p className="text-sm text-muted-foreground font-body">
              Personalize sua experiência no aplicativo
            </p>
          </div>
        </div>
        <Icon 
          name="ChevronDown" 
          size={20} 
          className={`text-muted-foreground transition-transform ${isExpanded ? 'rotate-180' : ''}`} 
        />
      </button>
      {isExpanded && (
        <div className="px-6 pb-6 border-t border-border">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
            {/* Appearance & Localization */}
            <div className="space-y-6">
              <h4 className="font-body font-medium text-primary">
                Aparência e Localização
              </h4>
              
              <Select
                label="Tema"
                description="Escolha como o aplicativo deve aparecer"
                options={themeOptions}
                value={formData?.theme}
                onChange={(value) => handleInputChange('theme', value)}
                className="mb-4"
              />

              <Select
                label="Idioma"
                description="Idioma da interface do usuário"
                options={languageOptions}
                value={formData?.language}
                onChange={(value) => handleInputChange('language', value)}
                className="mb-4"
              />

              <Select
                label="Fuso Horário"
                description="Para agendamento de posts"
                options={timezoneOptions}
                value={formData?.timezone}
                onChange={(value) => handleInputChange('timezone', value)}
              />
            </div>

            {/* Notifications */}
            <div className="space-y-6">
              <h4 className="font-body font-medium text-primary">
                Notificações
              </h4>
              
              <div className="space-y-4">
                <Checkbox
                  label="Notificações por Email"
                  description="Receber atualizações importantes por email"
                  checked={formData?.emailNotifications}
                  onChange={(e) => handleInputChange('emailNotifications', e?.target?.checked)}
                />

                <Checkbox
                  label="Notificações Push"
                  description="Receber notificações no navegador"
                  checked={formData?.pushNotifications}
                  onChange={(e) => handleInputChange('pushNotifications', e?.target?.checked)}
                />

                <Checkbox
                  label="Emails de Marketing"
                  description="Dicas, novidades e ofertas especiais"
                  checked={formData?.marketingEmails}
                  onChange={(e) => handleInputChange('marketingEmails', e?.target?.checked)}
                />

                <Checkbox
                  label="Relatórios Semanais"
                  description="Resumo semanal de performance"
                  checked={formData?.weeklyReports}
                  onChange={(e) => handleInputChange('weeklyReports', e?.target?.checked)}
                />
              </div>

              {/* Platform Alerts */}
              <div className="pt-4 border-t border-border">
                <h5 className="font-body font-medium text-primary mb-3">
                  Alertas por Plataforma
                </h5>
                <div className="space-y-3">
                  {Object.entries(formData?.platformAlerts)?.map(([platform, enabled]) => (
                    <Checkbox
                      key={platform}
                      label={`${platform?.charAt(0)?.toUpperCase() + platform?.slice(1)}`}
                      description={`Alertas de ${platform}`}
                      checked={enabled}
                      onChange={(e) => handlePlatformAlertChange(platform, e?.target?.checked)}
                      size="sm"
                    />
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Action Button */}
          <div className="flex items-center justify-end mt-6 pt-6 border-t border-border">
            <Button
              variant="default"
              onClick={handleSave}
              loading={isSaving}
              iconName="Save"
              iconPosition="left"
            >
              Salvar Preferências
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};

export default PreferencesSection;