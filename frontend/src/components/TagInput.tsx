import React, { useState, useEffect, useRef } from 'react';
import { apiService } from '../utils/api';

interface TagInputProps {
  tags: string[];
  onChange: (tags: string[]) => void;
  maxTags?: number;
  placeholder?: string;
  className?: string;
}

const TagInput: React.FC<TagInputProps> = ({
  tags,
  onChange,
  maxTags = 5,
  placeholder = "Add tags...",
  className = ""
}) => {
  const [inputValue, setInputValue] = useState('');
  const [suggestions, setSuggestions] = useState<string[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [activeSuggestion, setActiveSuggestion] = useState(-1);
  const inputRef = useRef<HTMLInputElement>(null);
  const suggestionsRef = useRef<HTMLDivElement>(null);

  // Fetch tag suggestions
  useEffect(() => {
    const fetchSuggestions = async () => {
      if (inputValue.length < 2) {
        setSuggestions([]);
        setShowSuggestions(false);
        return;
      }

      try {
        const response = await apiService.tags.getSuggestions(inputValue);
        if (response.data.success) {
          const filteredSuggestions = response.data.data.filter(
            (suggestion: string) => !tags.includes(suggestion)
          );
          setSuggestions(filteredSuggestions);
          setShowSuggestions(filteredSuggestions.length > 0);
        }
      } catch (error) {
        console.error('Failed to fetch tag suggestions:', error);
        setSuggestions([]);
        setShowSuggestions(false);
      }
    };

    const debounceTimer = setTimeout(fetchSuggestions, 300);
    return () => clearTimeout(debounceTimer);
  }, [inputValue, tags]);

  const addTag = (tag: string) => {
    const trimmedTag = tag.trim();
    if (
      trimmedTag &&
      !tags.includes(trimmedTag) &&
      tags.length < maxTags &&
      trimmedTag.length <= 50
    ) {
      onChange([...tags, trimmedTag]);
    }
    setInputValue('');
    setSuggestions([]);
    setShowSuggestions(false);
    setActiveSuggestion(-1);
  };

  const removeTag = (indexToRemove: number) => {
    onChange(tags.filter((_, index) => index !== indexToRemove));
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setInputValue(e.target.value);
    setActiveSuggestion(-1);
  };

  const handleInputKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (activeSuggestion >= 0 && suggestions[activeSuggestion]) {
        addTag(suggestions[activeSuggestion]);
      } else if (inputValue.trim()) {
        addTag(inputValue);
      }
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveSuggestion(prev => 
        prev < suggestions.length - 1 ? prev + 1 : prev
      );
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveSuggestion(prev => prev > 0 ? prev - 1 : -1);
    } else if (e.key === 'Escape') {
      setShowSuggestions(false);
      setActiveSuggestion(-1);
    } else if (e.key === 'Backspace' && !inputValue && tags.length > 0) {
      removeTag(tags.length - 1);
    }
  };

  const handleSuggestionClick = (suggestion: string) => {
    addTag(suggestion);
    inputRef.current?.focus();
  };

  const handleInputBlur = () => {
    // Delay hiding suggestions to allow for clicks
    setTimeout(() => {
      setShowSuggestions(false);
      setActiveSuggestion(-1);
    }, 200);
  };

  const handleInputFocus = () => {
    if (suggestions.length > 0) {
      setShowSuggestions(true);
    }
  };

  return (
    <div className={`relative ${className}`}>
      <div className="flex flex-wrap items-center gap-2 p-3 border border-gray-300 rounded-lg focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 min-h-[48px]">
        {/* Existing tags */}
        {tags.map((tag, index) => (
          <span
            key={index}
            className="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
          >
            {tag}
            <button
              type="button"
              onClick={() => removeTag(index)}
              className="ml-2 text-blue-600 hover:text-blue-800 focus:outline-none"
            >
              ×
            </button>
          </span>
        ))}

        {/* Input field */}
        {tags.length < maxTags && (
          <input
            ref={inputRef}
            type="text"
            value={inputValue}
            onChange={handleInputChange}
            onKeyDown={handleInputKeyDown}
            onBlur={handleInputBlur}
            onFocus={handleInputFocus}
            placeholder={tags.length === 0 ? placeholder : ''}
            className="flex-1 min-w-[120px] outline-none bg-transparent"
            maxLength={50}
          />
        )}
      </div>

      {/* Tag limit indicator */}
      <div className="flex justify-between items-center mt-1 text-sm text-gray-500">
        <span>
          {tags.length}/{maxTags} tags
        </span>
        {inputValue.length > 40 && (
          <span className="text-orange-500">
            {50 - inputValue.length} characters remaining
          </span>
        )}
      </div>

      {/* Suggestions dropdown */}
      {showSuggestions && suggestions.length > 0 && (
        <div
          ref={suggestionsRef}
          className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto"
        >
          {suggestions.map((suggestion, index) => (
            <button
              key={index}
              type="button"
              onClick={() => handleSuggestionClick(suggestion)}
              className={`w-full px-4 py-2 text-left hover:bg-gray-100 focus:bg-gray-100 focus:outline-none ${
                index === activeSuggestion ? 'bg-gray-100' : ''
              }`}
            >
              {suggestion}
            </button>
          ))}
        </div>
      )}

      {/* Validation messages */}
      {tags.length >= maxTags && (
        <p className="mt-1 text-sm text-orange-600">
          Maximum {maxTags} tags allowed
        </p>
      )}
    </div>
  );
};

export default TagInput;