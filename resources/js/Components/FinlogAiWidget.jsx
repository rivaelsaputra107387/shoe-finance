import React, { useState, useRef, useEffect } from 'react';
import axios from 'axios';
import { Bot, X, Send, Loader2, User, Sparkles } from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import LoginBear from '@/Components/LoginBear';

export default function FinlogAiWidget({ isOpen, onClose }) {
    const [messages, setMessages] = useState([
        { role: 'model', parts: [{ text: 'Halo! Saya Finlog AI Assistant. Ada yang bisa saya bantu terkait operasional akuntansi, aturan jurnal, atau penggunaan sistem?' }] }
    ]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [hasFetchedHistory, setHasFetchedHistory] = useState(false);
    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);

    // Fetch history on first open
    useEffect(() => {
        if (isOpen && !hasFetchedHistory) {
            const fetchHistory = async () => {
                try {
                    const res = await axios.get('/app/ai-chat/history');
                    if (res.data?.messages?.length > 0) {
                        setMessages(res.data.messages);
                    }
                } catch (error) {
                    console.error('Failed to fetch AI history:', error);
                } finally {
                    setHasFetchedHistory(true);
                }
            };
            fetchHistory();
        }
    }, [isOpen, hasFetchedHistory]);

    // Auto-scroll to bottom
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, isLoading, isOpen]);

    // Focus input when opened
    useEffect(() => {
        if (isOpen) {
            setTimeout(() => {
                inputRef.current?.focus();
            }, 300);
        }
    }, [isOpen]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!input.trim() || isLoading) return;

        const userMessage = { role: 'user', parts: [{ text: input.trim() }] };
        const newMessages = [...messages, userMessage];
        
        setMessages(newMessages);
        setInput('');
        setIsLoading(true);

        try {
            // Jangan kirim pesan error (isError = true) ke API
            const validMessages = newMessages.filter(msg => !msg.isError);

            const response = await axios.post('/app/ai-chat', {
                messages: validMessages
            });

            if (response.data?.reply) {
                setMessages(prev => [...prev, { role: 'model', parts: [{ text: response.data.reply }] }]);
            } else {
                throw new Error('Respons tidak valid');
            }
        } catch (error) {
            const errorMessage = error.response?.data?.error || 'Gagal terhubung ke AI. Silakan coba lagi.';
            setMessages(prev => [...prev, { role: 'model', parts: [{ text: `**Error:** ${errorMessage}` }], isError: true }]);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <>
            {/* Backdrop for mobile */}
            {isOpen && (
                <div 
                    className="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[60] lg:hidden"
                    onClick={onClose}
                />
            )}

            {/* Sidebar Drawer */}
            <div className={`
                fixed top-0 right-0 h-screen w-full sm:w-[400px] bg-white dark:bg-gray-900 shadow-2xl z-[70] flex flex-col transition-transform duration-300 ease-in-out border-l border-gray-200 dark:border-gray-800
                ${isOpen ? 'translate-x-0' : 'translate-x-full'}
            `}>
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-800 bg-emerald-50/50 dark:bg-emerald-900/10">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 flex items-end justify-center flex-shrink-0 drop-shadow-sm">
                            <LoginBear className="w-10 h-10" />
                        </div>
                        <div>
                            <h2 className="font-bold text-gray-900 dark:text-white text-sm">Finlog Assistant</h2>
                            <p className="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Powered by Gemini AI</p>
                        </div>
                    </div>
                    <button 
                        onClick={onClose}
                        className="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Chat Area */}
                <div className="flex-1 overflow-y-auto p-4 space-y-5 bg-gray-50/50 dark:bg-gray-950/50 scroll-smooth">
                    {messages.map((msg, index) => {
                        const isUser = msg.role === 'user';
                        return (
                            <div key={index} className={`flex gap-3 ${isUser ? 'flex-row-reverse' : ''}`}>
                                {isUser ? (
                                    <div className="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm border border-white/50 bg-indigo-100 dark:bg-indigo-900/50">
                                        <User className="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    </div>
                                ) : (
                                    <div className="w-8 h-8 flex items-end justify-center flex-shrink-0 drop-shadow-sm">
                                        <LoginBear className="w-8 h-8" />
                                    </div>
                                )}
                                <div className={`max-w-[80%] rounded-2xl p-3 text-sm shadow-sm overflow-hidden ${
                                    isUser 
                                        ? 'bg-indigo-600 text-white rounded-tr-sm' 
                                        : msg.isError 
                                            ? 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800/50 rounded-tl-sm'
                                            : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-tl-sm'
                                }`}>
                                    {isUser ? (
                                        <p className="whitespace-pre-wrap">{msg.parts[0].text}</p>
                                    ) : (
                                        <div className="prose prose-sm dark:prose-invert max-w-none prose-p:leading-relaxed prose-pre:bg-gray-100 dark:prose-pre:bg-gray-900 prose-pre:border prose-pre:border-gray-200 dark:prose-pre:border-gray-800 break-words">
                                            <ReactMarkdown>{msg.parts[0].text}</ReactMarkdown>
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                    
                    {isLoading && (
                        <div className="flex gap-3">
                            <div className="w-8 h-8 flex items-end justify-center flex-shrink-0 drop-shadow-sm">
                                <LoginBear className="w-8 h-8 animate-pulse" />
                            </div>
                            <div className="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl rounded-tl-sm p-4 shadow-sm flex items-center gap-2">
                                <Loader2 className="w-4 h-4 text-emerald-500 animate-spin" />
                                <span className="text-xs text-gray-500 dark:text-gray-400 font-medium animate-pulse">Berpikir...</span>
                            </div>
                        </div>
                    )}
                    <div ref={messagesEndRef} />
                </div>

                {/* Input Area */}
                <div className="p-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                    <form onSubmit={handleSubmit} className="relative flex items-center">
                        <input
                            ref={inputRef}
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            placeholder="Tanya soal jurnal, COA, dll..."
                            className="w-full pl-4 pr-12 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:focus:ring-emerald-400/20 dark:focus:border-emerald-400 transition-all text-sm text-gray-900 dark:text-white placeholder-gray-400"
                            disabled={isLoading}
                        />
                        <button
                            type="submit"
                            disabled={!input.trim() || isLoading}
                            className="absolute right-2 p-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg transition-colors flex items-center justify-center"
                        >
                            <Send className="w-4 h-4 ml-0.5" />
                        </button>
                    </form>
                    <div className="mt-2 text-center">
                        <span className="text-[10px] text-gray-400 dark:text-gray-500">
                            AI bisa saja salah. Pastikan untuk memverifikasi kebijakan internal.
                        </span>
                    </div>
                </div>
            </div>
        </>
    );
}
