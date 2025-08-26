import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';
import Input from '../../../components/ui/Input';
import { useToast } from '../../../components/ui/ToastNotificationSystem';

const ConnectionModal = ({ platform, isOpen, onClose, onConnect }) => {
  const [credentials, setCredentials] = useState({
    apiKey: '',
    accessToken: '',
    clientId: '',
    clientSecret: ''
  });
  const [isConnecting, setIsConnecting] = useState(false);
  const [errors, setErrors] = useState({});
  const { success, error } = useToast();

  // Mock credentials for testing
  const mockCredentials = {
    'google-ads': {
      apiKey: 'gads_api_key_123456',
      clientId: 'gads_client_id_789',
      clientSecret: 'gads_secret_abc123'
    },
    'meta': {
      accessToken: 'meta_token_xyz789',
      clientId: 'meta_app_id_456',
      clientSecret: 'meta_secret_def456'
    },
    'youtube': {
      apiKey: 'yt_api_key_789012',
      clientId: 'yt_client_id_345',
      clientSecret: 'yt_secret_ghi789'
    },
    'tiktok': {
      accessToken: 'tt_token_abc123',
      clientId: 'tt_app_id_678',
      clientSecret: 'tt_secret_jkl012'
    },
    'twitter': {
      apiKey: 'tw_api_key_345678',
      accessToken: 'tw_token_mno345',
      clientSecret: 'tw_secret_pqr678'
    },
    'instagram': {
      accessToken: 'ig_token_stu901',
      clientId: 'ig_app_id_234',
      clientSecret: 'ig_secret_vwx234'
    }
  };

  const handleInputChange = (field, value) => {
    setCredentials(prev => ({ ...prev, [field]: value }));
    if (errors?.[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
  };

  const validateCredentials = () => {
    const newErrors = {};
    const requiredFields = getRequiredFields();

    requiredFields?.forEach(field => {
      if (!credentials?.[field]) {
        newErrors[field] = 'Este campo é obrigatório';
      }
    });

    setErrors(newErrors);
    return Object.keys(newErrors)?.length === 0;
  };

  const getRequiredFields = () => {
    switch (platform?.id) {
      case 'google-ads':
        return ['apiKey', 'clientId', 'clientSecret'];
      case 'meta': case'instagram':
        return ['accessToken', 'clientId', 'clientSecret'];
      case 'youtube':
        return ['apiKey', 'clientId', 'clientSecret'];
      case 'tiktok':
        return ['accessToken', 'clientId', 'clientSecret'];
      case 'twitter':
        return ['apiKey', 'accessToken', 'clientSecret'];
      default:
        return ['apiKey'];
    }
  };

  const handleConnect = async () => {
    if (!validateCredentials()) return;

    setIsConnecting(true);
    
    try {
      // Simulate API connection
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      // Check if credentials match mock data
      const mockCreds = mockCredentials?.[platform?.id];
      const requiredFields = getRequiredFields();
      
      const isValid = requiredFields?.every(field => 
        credentials?.[field] === mockCreds?.[field]
      );

      if (!isValid) {
        throw new Error('Credenciais inválidas');
      }

      onConnect(platform?.id, credentials);
      success(`${platform?.name} conectado com sucesso!`);
      onClose();
      
      // Reset form
      setCredentials({
        apiKey: '',
        accessToken: '',
        clientId: '',
        clientSecret: ''
      });
    } catch (err) {
      error(`Erro ao conectar: ${err?.message}. Verifique suas credenciais.`);
    } finally {
      setIsConnecting(false);
    }
  };

  const handleTestConnection = async () => {
    if (!validateCredentials()) return;

    setIsConnecting(true);
    try {
      await new Promise(resolve => setTimeout(resolve, 1500));
      success('Conexão testada com sucesso!');
    } catch (err) {
      error('Falha no teste de conexão');
    } finally {
      setIsConnecting(false);
    }
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
        <div className="bg-card border border-border rounded-lg shadow-modal w-full max-w-md max-h-[90vh] overflow-y-auto">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-border">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-muted rounded-lg flex items-center justify-center">
                <Icon name="Link" size={20} className="text-muted-foreground" />
              </div>
              <div>
                <h2 className="font-body font-semibold text-lg text-card-foreground">
                  Conectar {platform?.name}
                </h2>
                <p className="text-sm text-muted-foreground">
                  Insira suas credenciais de API
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
          <div className="p-6 space-y-4">
            {/* Instructions */}
            <div className="p-4 bg-accent/10 border border-accent/20 rounded-lg">
              <div className="flex items-start gap-3">
                <Icon name="Info" size={16} className="text-accent mt-0.5" />
                <div className="text-sm text-accent-foreground">
                  <p className="font-medium mb-1">Credenciais de teste:</p>
                  <div className="text-xs space-y-1 font-body">
                    {Object.entries(mockCredentials?.[platform?.id] || {})?.map(([key, value]) => (
                      <div key={key}>
                        <span className="font-medium">{key}:</span> {value}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Form Fields */}
            {getRequiredFields()?.includes('apiKey') && (
              <Input
                label="API Key"
                type="text"
                placeholder="Insira sua API Key"
                value={credentials?.apiKey}
                onChange={(e) => handleInputChange('apiKey', e?.target?.value)}
                error={errors?.apiKey}
                required
              />
            )}

            {getRequiredFields()?.includes('accessToken') && (
              <Input
                label="Access Token"
                type="text"
                placeholder="Insira seu Access Token"
                value={credentials?.accessToken}
                onChange={(e) => handleInputChange('accessToken', e?.target?.value)}
                error={errors?.accessToken}
                required
              />
            )}

            {getRequiredFields()?.includes('clientId') && (
              <Input
                label="Client ID"
                type="text"
                placeholder="Insira seu Client ID"
                value={credentials?.clientId}
                onChange={(e) => handleInputChange('clientId', e?.target?.value)}
                error={errors?.clientId}
                required
              />
            )}

            {getRequiredFields()?.includes('clientSecret') && (
              <Input
                label="Client Secret"
                type="password"
                placeholder="Insira seu Client Secret"
                value={credentials?.clientSecret}
                onChange={(e) => handleInputChange('clientSecret', e?.target?.value)}
                error={errors?.clientSecret}
                required
              />
            )}
          </div>

          {/* Actions */}
          <div className="flex flex-col sm:flex-row gap-3 p-6 border-t border-border">
            <Button
              variant="outline"
              onClick={handleTestConnection}
              loading={isConnecting}
              iconName="Zap"
              iconPosition="left"
              className="flex-1"
            >
              Testar Conexão
            </Button>
            <Button
              variant="default"
              onClick={handleConnect}
              loading={isConnecting}
              iconName="Link"
              iconPosition="left"
              className="flex-1"
            >
              {isConnecting ? 'Conectando...' : 'Conectar'}
            </Button>
          </div>
        </div>
      </div>
    </>
  );
};

export default ConnectionModal;