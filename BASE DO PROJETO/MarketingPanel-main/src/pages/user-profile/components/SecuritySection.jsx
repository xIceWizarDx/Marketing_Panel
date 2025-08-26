import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Input from '../../../components/ui/Input';
import Button from '../../../components/ui/Button';


const SecuritySection = ({ security, onSave, isExpanded, onToggle }) => {
  const [passwordForm, setPasswordForm] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  });
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(security?.twoFactorEnabled || false);
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [isEnabling2FA, setIsEnabling2FA] = useState(false);
  const [errors, setErrors] = useState({});
  const [passwordStrength, setPasswordStrength] = useState(0);

  const activeSessions = [
    {
      id: 1,
      device: "Chrome no Windows",
      location: "São Paulo, SP",
      lastActive: "Agora",
      current: true,
      ip: "192.168.1.1"
    },
    {
      id: 2,
      device: "Safari no iPhone",
      location: "São Paulo, SP", 
      lastActive: "2 horas atrás",
      current: false,
      ip: "192.168.1.2"
    },
    {
      id: 3,
      device: "Chrome no Android",
      location: "Rio de Janeiro, RJ",
      lastActive: "1 dia atrás",
      current: false,
      ip: "192.168.1.3"
    }
  ];

  const calculatePasswordStrength = (password) => {
    let strength = 0;
    if (password?.length >= 8) strength += 25;
    if (/[a-z]/?.test(password)) strength += 25;
    if (/[A-Z]/?.test(password)) strength += 25;
    if (/[0-9]/?.test(password)) strength += 12.5;
    if (/[^A-Za-z0-9]/?.test(password)) strength += 12.5;
    return Math.min(strength, 100);
  };

  const handlePasswordChange = (field, value) => {
    const newForm = { ...passwordForm, [field]: value };
    setPasswordForm(newForm);
    
    if (field === 'newPassword') {
      setPasswordStrength(calculatePasswordStrength(value));
    }
    
    // Clear errors
    if (errors?.[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
  };

  const validatePasswordForm = () => {
    const newErrors = {};
    
    if (!passwordForm?.currentPassword) {
      newErrors.currentPassword = 'Senha atual é obrigatória';
    }
    
    if (!passwordForm?.newPassword) {
      newErrors.newPassword = 'Nova senha é obrigatória';
    } else if (passwordForm?.newPassword?.length < 8) {
      newErrors.newPassword = 'Senha deve ter pelo menos 8 caracteres';
    }
    
    if (passwordForm?.newPassword !== passwordForm?.confirmPassword) {
      newErrors.confirmPassword = 'Senhas não coincidem';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors)?.length === 0;
  };

  const handlePasswordSubmit = async () => {
    if (!validatePasswordForm()) return;
    
    setIsChangingPassword(true);
    // Simulate API call
    setTimeout(() => {
      onSave({ type: 'password', data: passwordForm });
      setPasswordForm({ currentPassword: '', newPassword: '', confirmPassword: '' });
      setPasswordStrength(0);
      setIsChangingPassword(false);
    }, 1500);
  };

  const handleToggle2FA = async () => {
    setIsEnabling2FA(true);
    // Simulate API call
    setTimeout(() => {
      const newValue = !twoFactorEnabled;
      setTwoFactorEnabled(newValue);
      onSave({ type: '2fa', enabled: newValue });
      setIsEnabling2FA(false);
    }, 1000);
  };

  const handleTerminateSession = (sessionId) => {
    // Simulate session termination
    console.log('Terminating session:', sessionId);
  };

  const getPasswordStrengthColor = () => {
    if (passwordStrength < 25) return 'bg-error';
    if (passwordStrength < 50) return 'bg-warning';
    if (passwordStrength < 75) return 'bg-accent';
    return 'bg-success';
  };

  const getPasswordStrengthText = () => {
    if (passwordStrength < 25) return 'Muito fraca';
    if (passwordStrength < 50) return 'Fraca';
    if (passwordStrength < 75) return 'Boa';
    return 'Forte';
  };

  return (
    <div className="bg-card border border-border rounded-lg mb-4">
      <button
        onClick={onToggle}
        className="w-full flex items-center justify-between p-6 text-left hover:bg-muted/50 transition-smooth"
        aria-expanded={isExpanded}
      >
        <div className="flex items-center gap-3">
          <Icon name="Shield" size={20} className="text-accent" />
          <div>
            <h3 className="font-body font-semibold text-lg text-primary">
              Segurança
            </h3>
            <p className="text-sm text-muted-foreground font-body">
              Gerencie senha, autenticação e sessões ativas
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
          <div className="space-y-8 mt-6">
            {/* Password Change */}
            <div>
              <h4 className="font-body font-medium text-primary mb-4">
                Alterar Senha
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <Input
                    label="Senha Atual"
                    type="password"
                    value={passwordForm?.currentPassword}
                    onChange={(e) => handlePasswordChange('currentPassword', e?.target?.value)}
                    error={errors?.currentPassword}
                    placeholder="Digite sua senha atual"
                    required
                  />
                </div>
                
                <div>
                  <Input
                    label="Nova Senha"
                    type="password"
                    value={passwordForm?.newPassword}
                    onChange={(e) => handlePasswordChange('newPassword', e?.target?.value)}
                    error={errors?.newPassword}
                    placeholder="Digite a nova senha"
                    required
                  />
                  {passwordForm?.newPassword && (
                    <div className="mt-2">
                      <div className="flex items-center gap-2 mb-1">
                        <div className="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                          <div 
                            className={`h-full transition-all duration-300 ${getPasswordStrengthColor()}`}
                            style={{ width: `${passwordStrength}%` }}
                          />
                        </div>
                        <span className="text-xs font-body text-muted-foreground">
                          {getPasswordStrengthText()}
                        </span>
                      </div>
                    </div>
                  )}
                </div>
                
                <div>
                  <Input
                    label="Confirmar Nova Senha"
                    type="password"
                    value={passwordForm?.confirmPassword}
                    onChange={(e) => handlePasswordChange('confirmPassword', e?.target?.value)}
                    error={errors?.confirmPassword}
                    placeholder="Confirme a nova senha"
                    required
                  />
                </div>
              </div>
              
              <div className="flex justify-end mt-4">
                <Button
                  variant="default"
                  onClick={handlePasswordSubmit}
                  loading={isChangingPassword}
                  iconName="Key"
                  iconPosition="left"
                  disabled={!passwordForm?.currentPassword || !passwordForm?.newPassword || !passwordForm?.confirmPassword}
                >
                  Alterar Senha
                </Button>
              </div>
            </div>

            {/* Two-Factor Authentication */}
            <div className="pt-6 border-t border-border">
              <h4 className="font-body font-medium text-primary mb-4">
                Autenticação de Dois Fatores
              </h4>
              <div className="flex items-start gap-4 p-4 bg-muted/30 rounded-lg">
                <Icon 
                  name={twoFactorEnabled ? "ShieldCheck" : "Shield"} 
                  size={24} 
                  className={twoFactorEnabled ? "text-success" : "text-muted-foreground"} 
                />
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-2">
                    <h5 className="font-body font-medium text-primary">
                      Autenticação 2FA
                    </h5>
                    <span className={`px-2 py-1 rounded-full text-xs font-body ${
                      twoFactorEnabled 
                        ? 'bg-success/10 text-success' :'bg-muted text-muted-foreground'
                    }`}>
                      {twoFactorEnabled ? 'Ativada' : 'Desativada'}
                    </span>
                  </div>
                  <p className="text-sm text-muted-foreground font-body mb-4">
                    {twoFactorEnabled 
                      ? 'Sua conta está protegida com autenticação de dois fatores.'
                      : 'Adicione uma camada extra de segurança à sua conta.'
                    }
                  </p>
                  <Button
                    variant={twoFactorEnabled ? "outline" : "default"}
                    onClick={handleToggle2FA}
                    loading={isEnabling2FA}
                    iconName={twoFactorEnabled ? "ShieldOff" : "ShieldCheck"}
                    iconPosition="left"
                    size="sm"
                  >
                    {twoFactorEnabled ? 'Desativar 2FA' : 'Ativar 2FA'}
                  </Button>
                </div>
              </div>
            </div>

            {/* Active Sessions */}
            <div className="pt-6 border-t border-border">
              <h4 className="font-body font-medium text-primary mb-4">
                Sessões Ativas
              </h4>
              <div className="space-y-3">
                {activeSessions?.map((session) => (
                  <div key={session?.id} className="flex items-center justify-between p-4 border border-border rounded-lg">
                    <div className="flex items-center gap-3">
                      <Icon 
                        name={session?.device?.includes('iPhone') ? 'Smartphone' : 'Monitor'} 
                        size={20} 
                        className="text-muted-foreground" 
                      />
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-body font-medium text-primary">
                            {session?.device}
                          </span>
                          {session?.current && (
                            <span className="px-2 py-1 bg-success/10 text-success text-xs font-body rounded-full">
                              Atual
                            </span>
                          )}
                        </div>
                        <div className="text-sm text-muted-foreground font-body">
                          {session?.location} • {session?.lastActive}
                        </div>
                        <div className="text-xs text-muted-foreground font-body">
                          IP: {session?.ip}
                        </div>
                      </div>
                    </div>
                    
                    {!session?.current && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleTerminateSession(session?.id)}
                        iconName="LogOut"
                        className="text-error hover:text-error hover:bg-error/10"
                      >
                        Encerrar
                      </Button>
                    )}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SecuritySection;