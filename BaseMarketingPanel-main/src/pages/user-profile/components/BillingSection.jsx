import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';

const BillingSection = ({ billing, onSave, isExpanded, onToggle }) => {
  const [isUpdatingPayment, setIsUpdatingPayment] = useState(false);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    })?.format(amount);
  };

  const formatDate = (dateString) => {
    return new Date(dateString)?.toLocaleDateString('pt-BR');
  };

  const handleUpdatePayment = async () => {
    setIsUpdatingPayment(true);
    // Simulate API call
    setTimeout(() => {
      onSave({ type: 'payment', data: {} });
      setIsUpdatingPayment(false);
    }, 1500);
  };

  const handleDownloadInvoice = (invoiceId) => {
    // Simulate invoice download
    console.log('Downloading invoice:', invoiceId);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active':
        return 'bg-success/10 text-success';
      case 'cancelled':
        return 'bg-error/10 text-error';
      case 'pending':
        return 'bg-warning/10 text-warning';
      default:
        return 'bg-muted text-muted-foreground';
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case 'active':
        return 'Ativa';
      case 'cancelled':
        return 'Cancelada';
      case 'pending':
        return 'Pendente';
      default:
        return 'Desconhecido';
    }
  };

  const getInvoiceStatusColor = (status) => {
    switch (status) {
      case 'paid':
        return 'text-success';
      case 'pending':
        return 'text-warning';
      case 'overdue':
        return 'text-error';
      default:
        return 'text-muted-foreground';
    }
  };

  const getInvoiceStatusText = (status) => {
    switch (status) {
      case 'paid':
        return 'Paga';
      case 'pending':
        return 'Pendente';
      case 'overdue':
        return 'Vencida';
      default:
        return 'Desconhecido';
    }
  };

  return (
    <div className="bg-card border border-border rounded-lg mb-4">
      <button
        onClick={onToggle}
        className="w-full flex items-center justify-between p-6 text-left hover:bg-muted/50 transition-smooth"
        aria-expanded={isExpanded}
      >
        <div className="flex items-center gap-3">
          <Icon name="CreditCard" size={20} className="text-accent" />
          <div>
            <h3 className="font-body font-semibold text-lg text-primary">
              Cobrança e Pagamento
            </h3>
            <p className="text-sm text-muted-foreground font-body">
              Gerencie assinatura, pagamentos e faturas
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
            {/* Subscription Status */}
            <div>
              <h4 className="font-body font-medium text-primary mb-4">
                Status da Assinatura
              </h4>
              <div className="p-4 bg-muted/30 rounded-lg">
                <div className="flex items-center justify-between mb-4">
                  <div>
                    <div className="flex items-center gap-2 mb-2">
                      <h5 className="font-body font-medium text-primary">
                        {billing?.plan?.name}
                      </h5>
                      <span className={`px-2 py-1 rounded-full text-xs font-body ${getStatusColor(billing?.status)}`}>
                        {getStatusText(billing?.status)}
                      </span>
                    </div>
                    <p className="text-sm text-muted-foreground font-body">
                      {billing?.plan?.description}
                    </p>
                  </div>
                  <div className="text-right">
                    <div className="text-2xl font-body font-bold text-primary">
                      {formatCurrency(billing?.plan?.price)}
                    </div>
                    <div className="text-sm text-muted-foreground font-body">
                      por mês
                    </div>
                  </div>
                </div>
                
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                  <div>
                    <span className="text-muted-foreground font-body">Próxima cobrança:</span>
                    <div className="font-body font-medium text-primary">
                      {formatDate(billing?.nextBilling)}
                    </div>
                  </div>
                  <div>
                    <span className="text-muted-foreground font-body">Método de pagamento:</span>
                    <div className="font-body font-medium text-primary">
                      {billing?.paymentMethod?.type}
                    </div>
                  </div>
                  <div>
                    <span className="text-muted-foreground font-body">Renovação automática:</span>
                    <div className="font-body font-medium text-primary">
                      {billing?.autoRenew ? 'Ativada' : 'Desativada'}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Payment Method */}
            <div className="pt-6 border-t border-border">
              <h4 className="font-body font-medium text-primary mb-4">
                Método de Pagamento
              </h4>
              <div className="flex items-center justify-between p-4 border border-border rounded-lg">
                <div className="flex items-center gap-3">
                  <Icon name="CreditCard" size={20} className="text-muted-foreground" />
                  <div>
                    <div className="font-body font-medium text-primary">
                      {billing?.paymentMethod?.brand} •••• {billing?.paymentMethod?.last4}
                    </div>
                    <div className="text-sm text-muted-foreground font-body">
                      Expira em {billing?.paymentMethod?.expiry}
                    </div>
                  </div>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleUpdatePayment}
                  loading={isUpdatingPayment}
                  iconName="Edit"
                  iconPosition="left"
                >
                  Atualizar
                </Button>
              </div>
            </div>

            {/* Billing History */}
            <div className="pt-6 border-t border-border">
              <h4 className="font-body font-medium text-primary mb-4">
                Histórico de Faturas
              </h4>
              <div className="space-y-3">
                {billing?.invoices?.map((invoice) => (
                  <div key={invoice?.id} className="flex items-center justify-between p-4 border border-border rounded-lg">
                    <div className="flex items-center gap-4">
                      <Icon name="FileText" size={20} className="text-muted-foreground" />
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-body font-medium text-primary">
                            Fatura #{invoice?.number}
                          </span>
                          <span className={`text-xs font-body ${getInvoiceStatusColor(invoice?.status)}`}>
                            {getInvoiceStatusText(invoice?.status)}
                          </span>
                        </div>
                        <div className="text-sm text-muted-foreground font-body">
                          {formatDate(invoice?.date)} • {formatCurrency(invoice?.amount)}
                        </div>
                      </div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleDownloadInvoice(invoice?.id)}
                        iconName="Download"
                        iconPosition="left"
                      >
                        Baixar
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
              
              <div className="flex justify-center mt-4">
                <Button variant="outline" size="sm">
                  Ver Todas as Faturas
                </Button>
              </div>
            </div>

            {/* Plan Management */}
            <div className="pt-6 border-t border-border">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-body font-medium text-primary mb-1">
                    Gerenciar Plano
                  </h4>
                  <p className="text-sm text-muted-foreground font-body">
                    Altere seu plano ou cancele sua assinatura
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Button variant="outline" size="sm">
                    Alterar Plano
                  </Button>
                  <Button variant="ghost" size="sm" className="text-error hover:text-error hover:bg-error/10">
                    Cancelar Assinatura
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default BillingSection;