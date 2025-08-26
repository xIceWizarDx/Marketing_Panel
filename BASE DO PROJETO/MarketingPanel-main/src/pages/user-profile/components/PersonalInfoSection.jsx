import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Input from '../../../components/ui/Input';
import Button from '../../../components/ui/Button';

const PersonalInfoSection = ({ user, onSave, isExpanded, onToggle }) => {
  const [formData, setFormData] = useState({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    company: user?.company || '',
    position: user?.position || '',
    website: user?.website || ''
  });
  const [isEditing, setIsEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [errors, setErrors] = useState({});

  const handleInputChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors?.[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    if (!formData?.name?.trim()) {
      newErrors.name = 'Nome é obrigatório';
    }
    
    if (!formData?.email?.trim()) {
      newErrors.email = 'Email é obrigatório';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/?.test(formData?.email)) {
      newErrors.email = 'Email inválido';
    }
    
    if (formData?.phone && !/^\(\d{2}\)\s\d{4,5}-\d{4}$/?.test(formData?.phone)) {
      newErrors.phone = 'Formato: (11) 99999-9999';
    }
    
    if (formData?.website && !/^https?:\/\/.+/?.test(formData?.website)) {
      newErrors.website = 'URL deve começar com http:// ou https://';
    }

    setErrors(newErrors);
    return Object.keys(newErrors)?.length === 0;
  };

  const handleSave = async () => {
    if (!validateForm()) return;
    
    setIsSaving(true);
    // Simulate API call
    setTimeout(() => {
      onSave(formData);
      setIsEditing(false);
      setIsSaving(false);
    }, 1000);
  };

  const handleCancel = () => {
    setFormData({
      name: user?.name || '',
      email: user?.email || '',
      phone: user?.phone || '',
      company: user?.company || '',
      position: user?.position || '',
      website: user?.website || ''
    });
    setErrors({});
    setIsEditing(false);
  };

  const formatPhoneNumber = (value) => {
    const numbers = value?.replace(/\D/g, '');
    if (numbers?.length <= 11) {
      return numbers?.replace(/(\d{2})(\d{4,5})(\d{4})/, '($1) $2-$3');
    }
    return value;
  };

  return (
    <div className="bg-card border border-border rounded-lg mb-4">
      <button
        onClick={onToggle}
        className="w-full flex items-center justify-between p-6 text-left hover:bg-muted/50 transition-smooth"
        aria-expanded={isExpanded}
      >
        <div className="flex items-center gap-3">
          <Icon name="User" size={20} className="text-accent" />
          <div>
            <h3 className="font-body font-semibold text-lg text-primary">
              Informações Pessoais
            </h3>
            <p className="text-sm text-muted-foreground font-body">
              Gerencie seus dados pessoais e profissionais
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
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            {/* Personal Information */}
            <div className="space-y-4">
              <h4 className="font-body font-medium text-primary mb-4">
                Dados Pessoais
              </h4>
              
              <Input
                label="Nome Completo"
                type="text"
                value={formData?.name}
                onChange={(e) => handleInputChange('name', e?.target?.value)}
                error={errors?.name}
                disabled={!isEditing}
                required
                placeholder="Seu nome completo"
              />

              <Input
                label="Email"
                type="email"
                value={formData?.email}
                onChange={(e) => handleInputChange('email', e?.target?.value)}
                error={errors?.email}
                disabled={!isEditing}
                required
                placeholder="seu@email.com"
              />

              <Input
                label="Telefone"
                type="tel"
                value={formData?.phone}
                onChange={(e) => handleInputChange('phone', formatPhoneNumber(e?.target?.value))}
                error={errors?.phone}
                disabled={!isEditing}
                placeholder="(11) 99999-9999"
                description="Formato brasileiro com DDD"
              />
            </div>

            {/* Business Information */}
            <div className="space-y-4">
              <h4 className="font-body font-medium text-primary mb-4">
                Informações Profissionais
              </h4>
              
              <Input
                label="Empresa"
                type="text"
                value={formData?.company}
                onChange={(e) => handleInputChange('company', e?.target?.value)}
                disabled={!isEditing}
                placeholder="Nome da empresa"
              />

              <Input
                label="Cargo"
                type="text"
                value={formData?.position}
                onChange={(e) => handleInputChange('position', e?.target?.value)}
                disabled={!isEditing}
                placeholder="Seu cargo atual"
              />

              <Input
                label="Website"
                type="url"
                value={formData?.website}
                onChange={(e) => handleInputChange('website', e?.target?.value)}
                error={errors?.website}
                disabled={!isEditing}
                placeholder="https://seusite.com"
              />
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-border">
            {isEditing ? (
              <>
                <Button
                  variant="outline"
                  onClick={handleCancel}
                  disabled={isSaving}
                >
                  Cancelar
                </Button>
                <Button
                  variant="default"
                  onClick={handleSave}
                  loading={isSaving}
                  iconName="Save"
                  iconPosition="left"
                >
                  Salvar Alterações
                </Button>
              </>
            ) : (
              <Button
                variant="outline"
                onClick={() => setIsEditing(true)}
                iconName="Edit"
                iconPosition="left"
              >
                Editar Informações
              </Button>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default PersonalInfoSection;