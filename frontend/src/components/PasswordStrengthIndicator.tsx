import React from 'react';

interface PasswordStrengthIndicatorProps {
  password: string;
  className?: string;
}

const PasswordStrengthIndicator: React.FC<PasswordStrengthIndicatorProps> = ({ 
  password, 
  className = '' 
}) => {
  const calculateStrength = (password: string): number => {
    let score = 0;
    
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    
    return score;
  };

  const getStrengthInfo = (score: number) => {
    if (score === 0) return { label: '', color: '', width: '0%' };
    if (score <= 2) return { label: 'Weak', color: 'bg-red-500', width: '33%' };
    if (score <= 4) return { label: 'Medium', color: 'bg-yellow-500', width: '66%' };
    return { label: 'Strong', color: 'bg-green-500', width: '100%' };
  };

  const getRequirements = (password: string) => {
    return [
      { text: 'At least 8 characters', met: password.length >= 8 },
      { text: 'Contains lowercase letter', met: /[a-z]/.test(password) },
      { text: 'Contains uppercase letter', met: /[A-Z]/.test(password) },
      { text: 'Contains number', met: /[0-9]/.test(password) },
      { text: 'Contains special character', met: /[^A-Za-z0-9]/.test(password) },
    ];
  };

  if (!password) return null;

  const strength = calculateStrength(password);
  const strengthInfo = getStrengthInfo(strength);
  const requirements = getRequirements(password);

  return (
    <div className={`mt-2 ${className}`}>
      {/* Strength Bar */}
      <div className="mb-2">
        <div className="flex justify-between items-center mb-1">
          <span className="text-xs text-gray-600">Password strength</span>
          {strengthInfo.label && (
            <span className={`text-xs font-medium ${
              strengthInfo.label === 'Weak' ? 'text-red-600' :
              strengthInfo.label === 'Medium' ? 'text-yellow-600' :
              'text-green-600'
            }`}>
              {strengthInfo.label}
            </span>
          )}
        </div>
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className={`h-2 rounded-full transition-all duration-300 ${strengthInfo.color}`}
            style={{ width: strengthInfo.width }}
          />
        </div>
      </div>

      {/* Requirements */}
      <div className="space-y-1">
        {requirements.map((req, index) => (
          <div key={index} className="flex items-center text-xs">
            <div className={`w-3 h-3 rounded-full mr-2 flex items-center justify-center ${
              req.met ? 'bg-green-500' : 'bg-gray-300'
            }`}>
              {req.met && (
                <svg className="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                </svg>
              )}
            </div>
            <span className={req.met ? 'text-green-600' : 'text-gray-500'}>
              {req.text}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
};

export default PasswordStrengthIndicator;