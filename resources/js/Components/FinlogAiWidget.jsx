import React, { useState, useRef, useEffect } from 'react';
import axios from 'axios';
import { X, Send, Loader2, User, Sparkles, Pencil, Trash2, Copy, Check, Menu, Plus, MessageSquare } from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import LoginBear from '@/Components/LoginBear';

const SUGGESTED_PROMPTS = [
    { icon: '💰', label: 'Saldo Kas & Bank', prompt: 'Berapa total saldo kas dan bank saat ini?' },
    { icon: '📊', label: 'Jurnal Bulan Ini', prompt: 'Berapa transaksi jurnal di periode aktif? Berapa yang sudah posted dan yang masih draft?' },
    { icon: '📈', label: 'Laba Bersih', prompt: 'Berapa estimasi laba bersih di periode aktif saat ini?' },
    { icon: '⚖️', label: 'Perbandingan Periode', prompt: 'Bandingkan laba bersih 3 bulan terakhir, ada tren apa?' },
    { icon: '💸', label: 'Top Beban', prompt: 'Apa saja 5 akun beban terbesar di periode aktif?' },
    { icon: '🏦', label: 'Mutasi Pending', prompt: 'Ada berapa mutasi bank yang belum dijurnalkan?' },
    { icon: '📋', label: 'Status Periode', prompt: 'Apa status periode akuntansi saat ini dan periode apa saja yang ada di sistem?' },
    { icon: '💳', label: 'Hutang & Piutang', prompt: 'Berapa total saldo piutang usaha dan hutang usaha saat ini?' },
];

export default function FinlogAiWidget({ isOpen, onClose }) {
    const [messages, setMessages] = useState([
        { role: 'model', parts: [{ text: 'Halo! Saya Finlog AI Assistant. Ada yang bisa saya bantu terkait operasional akuntansi, aturan jurnal, atau penggunaan sistem?' }] }
    ]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [hasFetchedHistory, setHasFetchedHistory] = useState(false);
    const [editIndex, setEditIndex] = useState(null);
    const [copiedIndex, setCopiedIndex] = useState(null);
    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);

    // Sessions State
    const [sessions, setSessions] = useState([]);
    const [activeSessionId, setActiveSessionId] = useState(null);
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);
    const [editingSessionId, setEditingSessionId] = useState(null);
    const [editSessionTitle, setEditSessionTitle] = useState('');

    const fetchSessions = async () => {
        try {
            const res = await axios.get('/app/ai-chat/sessions');
            setSessions(res.data.sessions);
        } catch (error) {
            console.error('Failed to fetch AI sessions:', error);
        }
    };

    // Fetch history and sessions on first open
    useEffect(() => {
        if (isOpen && !hasFetchedHistory) {
            const init = async () => {
                await fetchSessions();
                try {
                    const res = await axios.get('/app/ai-chat/history');
                    if (res.data?.messages?.length > 0) {
                        setMessages(res.data.messages);
                        setActiveSessionId(res.data.session_id);
                    }
                } catch (error) {
                    console.error('Failed to fetch AI history:', error);
                } finally {
                    setHasFetchedHistory(true);
                }
            };
            init();
        }
    }, [isOpen, hasFetchedHistory]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isLoading, isOpen]);

    useEffect(() => {
        if (isOpen) setTimeout(() => inputRef.current?.focus(), 300);
    }, [isOpen]);

    const startNewChat = async () => {
        setIsLoading(true);
        try {
            const res = await axios.post('/app/ai-chat/sessions');
            setActiveSessionId(res.data.session.id);
            setMessages([{ role: 'model', parts: [{ text: 'Halo! Saya Finlog AI Assistant. Ada yang bisa saya bantu terkait operasional akuntansi, aturan jurnal, atau penggunaan sistem?' }] }]);
            await fetchSessions();
            if(window.innerWidth < 1024) setIsSidebarOpen(false);
        } catch (error) {
            console.error('Failed to create new chat:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const loadSession = async (id) => {
        setIsLoading(true);
        try {
            const res = await axios.get(`/app/ai-chat/history/${id}`);
            if (res.data?.messages?.length > 0) {
                setMessages(res.data.messages);
            } else {
                setMessages([{ role: 'model', parts: [{ text: 'Halo! Saya Finlog AI Assistant. Ada yang bisa saya bantu terkait operasional akuntansi, aturan jurnal, atau penggunaan sistem?' }] }]);
            }
            setActiveSessionId(id);
            if(window.innerWidth < 1024) setIsSidebarOpen(false);
        } catch (error) {
            console.error('Failed to load session:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleRenameSubmit = async (id) => {
        if (!editSessionTitle.trim()) {
            setEditingSessionId(null);
            return;
        }
        try {
            await axios.put(`/app/ai-chat/sessions/${id}`, { title: editSessionTitle });
            setEditingSessionId(null);
            fetchSessions();
        } catch (error) {
            console.error('Failed to rename session:', error);
        }
    };

    const handleDeleteSession = async (id) => {
        if (!confirm('Hapus percakapan ini?')) return;
        try {
            await axios.delete(`/app/ai-chat/sessions/${id}`);
            await fetchSessions();
            if (activeSessionId === id) {
                // Load latest or new
                const res = await axios.get('/app/ai-chat/history');
                if (res.data?.messages?.length > 0) {
                    setMessages(res.data.messages);
                    setActiveSessionId(res.data.session_id);
                } else {
                    startNewChat();
                }
            }
        } catch (error) {
            console.error('Failed to delete session:', error);
        }
    };

    const sendMessage = async (text) => {
        if (!text.trim() || isLoading) return;
        
        let baseMessages = messages;
        if (editIndex !== null) {
            baseMessages = messages.slice(0, editIndex);
            setEditIndex(null);
        }
        
        const userMessage = { role: 'user', parts: [{ text: text.trim() }] };
        const newMessages = [...baseMessages, userMessage];
        setMessages(newMessages);
        setInput('');
        setIsLoading(true);

        try {
            const validMessages = newMessages.filter(msg => !msg.isError);
            const payload = { messages: validMessages };
            if (activeSessionId) payload.session_id = activeSessionId;

            const response = await axios.post('/app/ai-chat', payload);
            if (response.data?.reply) {
                setMessages(prev => [...prev, { role: 'model', parts: [{ text: response.data.reply }] }]);
                if (response.data.session_id && response.data.session_id !== activeSessionId) {
                    setActiveSessionId(response.data.session_id);
                    fetchSessions();
                }
            } else {
                throw new Error('Respons tidak valid');
            }
        } catch (error) {
            let errorMessage = 'Gagal terhubung ke AI. Silakan coba lagi.';
            
            if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
                errorMessage = '⏱️ Timeout: AI membutuhkan terlalu lama untuk merespons. Coba lagi dengan pertanyaan yang lebih singkat.';
            } else if (error.response?.data?.error) {
                errorMessage = error.response.data.error;
            } else if (error.response?.status === 429) {
                errorMessage = '⚠️ Rate limit Gemini API tercapai. Tunggu beberapa detik lalu coba lagi.';
            } else if (error.response?.status === 503) {
                errorMessage = '🔌 Server Gemini sedang tidak tersedia. Coba lagi dalam beberapa saat.';
            } else if (!navigator.onLine) {
                errorMessage = '📶 Tidak ada koneksi internet. Periksa jaringanmu.';
            }
            
            setMessages(prev => [...prev, { role: 'model', parts: [{ text: `**Error:** ${errorMessage}` }], isError: true }]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSubmit = (e) => { e.preventDefault(); sendMessage(input); };
    const handleChipClick = (prompt) => sendMessage(prompt);

    const handleEditPrompt = (index) => {
        const textToEdit = messages[index].parts[0].text;
        setInput(textToEdit);
        setEditIndex(index);
        setTimeout(() => inputRef.current?.focus(), 100);
    };

    const handleCopy = (text, index) => {
        navigator.clipboard.writeText(text);
        setCopiedIndex(index);
        setTimeout(() => setCopiedIndex(null), 2000);
    };

    const showChips = messages.length === 1 && messages[0].role === 'model';
    
    let lastUserIndex = -1;
    for (let i = messages.length - 1; i >= 0; i--) {
        if (messages[i].role === 'user') {
            lastUserIndex = i;
            break;
        }
    }

    useEffect(() => {
        if (input === '' && editIndex !== null) {
            setEditIndex(null);
        }
    }, [input, editIndex]);

    return (
        <>
            {isOpen && (
                <div
                    className="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[60] lg:hidden"
                    onClick={onClose}
                />
            )}

            <div className={`
                fixed top-0 right-0 h-screen w-full sm:w-[400px] bg-white dark:bg-gray-900 shadow-2xl z-[70] flex flex-col transition-transform duration-300 ease-in-out border-l border-gray-200 dark:border-gray-800 overflow-hidden
                ${isOpen ? 'translate-x-0' : 'translate-x-full'}
            `}>
                
                {/* Sidebar Drawer */}
                <div className={`
                    absolute inset-y-0 left-0 w-64 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-xl z-[80] transform transition-transform duration-300 flex flex-col
                    ${isSidebarOpen ? 'translate-x-0' : '-translate-x-full'}
                `}>
                    <div className="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <h3 className="font-semibold text-gray-800 dark:text-gray-200 text-sm">Riwayat Obrolan</h3>
                        <button onClick={() => setIsSidebarOpen(false)} className="p-1 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                            <X className="w-4 h-4" />
                        </button>
                    </div>
                    
                    <div className="p-3">
                        <button 
                            onClick={startNewChat}
                            className="w-full flex items-center justify-center gap-2 py-2 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors"
                        >
                            <Plus className="w-4 h-4" /> Percakapan Baru
                        </button>
                    </div>

                    <div className="flex-1 overflow-y-auto p-2 space-y-1">
                        {sessions.map(session => (
                            <div key={session.id} className={`group flex flex-col gap-1 rounded-lg p-2 transition-colors ${activeSessionId === session.id ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-2 border-indigo-500' : 'hover:bg-gray-100 dark:hover:bg-gray-800 border-l-2 border-transparent'}`}>
                                <div className="flex items-start justify-between gap-2">
                                    <button 
                                        onClick={() => loadSession(session.id)}
                                        className="flex-1 text-left flex items-start gap-2 overflow-hidden"
                                    >
                                        <MessageSquare className="w-4 h-4 mt-0.5 text-gray-400 shrink-0" />
                                        {editingSessionId === session.id ? (
                                            <input 
                                                type="text" 
                                                value={editSessionTitle}
                                                onChange={(e) => setEditSessionTitle(e.target.value)}
                                                onBlur={() => handleRenameSubmit(session.id)}
                                                onKeyDown={(e) => e.key === 'Enter' && handleRenameSubmit(session.id)}
                                                autoFocus
                                                className="w-full text-xs p-1 border border-indigo-300 rounded"
                                            />
                                        ) : (
                                            <div className="flex flex-col overflow-hidden">
                                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{session.title}</span>
                                                <span className="text-[10px] text-gray-400">{session.updated_at}</span>
                                            </div>
                                        )}
                                    </button>
                                    
                                    {!editingSessionId && (
                                        <div className="opacity-0 group-hover:opacity-100 flex items-center gap-1 shrink-0">
                                            <button onClick={() => { setEditingSessionId(session.id); setEditSessionTitle(session.title); }} className="p-1 text-gray-400 hover:text-indigo-500">
                                                <Pencil className="w-3 h-3" />
                                            </button>
                                            <button onClick={() => handleDeleteSession(session.id)} className="p-1 text-gray-400 hover:text-red-500">
                                                <Trash2 className="w-3 h-3" />
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-800 bg-emerald-50/50 dark:bg-emerald-900/10">
                    <div className="flex items-center gap-3">
                        <button 
                            onClick={() => setIsSidebarOpen(!isSidebarOpen)}
                            className="p-1.5 text-emerald-700 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:bg-emerald-900/40 rounded-lg transition-colors"
                            title="Riwayat Percakapan"
                        >
                            <Menu className="w-5 h-5" />
                        </button>
                        <div className="w-10 h-10 flex items-end justify-center flex-shrink-0 drop-shadow-sm">
                            <LoginBear className="w-10 h-10" />
                        </div>
                        <div>
                            <h2 className="font-bold text-gray-900 dark:text-white text-sm">Finlog Assistant</h2>
                            <p className="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Powered by Gemini AI</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={onClose}
                            className="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {/* Chat Area */}
                <div className="flex-1 overflow-y-auto p-4 space-y-5 bg-gray-50/50 dark:bg-gray-950/50 scroll-smooth">
                    {messages.map((msg, index) => {
                        const isUser = msg.role === 'user';
                        
                        let displayText = msg.parts[0].text;
                        let suggestions = [];
                        
                        if (!isUser && !msg.isError) {
                            const match = displayText.match(/SUGGESTIONS:\s*(.*)/i);
                            if (match) {
                                suggestions = match[1].split('|').map(s => s.trim()).filter(Boolean);
                                displayText = displayText.replace(/SUGGESTIONS:\s*(.*)/i, '').trim();
                            }
                        }

                        return (
                            <div key={index} className={`flex gap-3 ${isUser ? 'flex-row-reverse' : ''} group`}>
                                {isUser ? (
                                    <div className="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm border border-white/50 bg-indigo-100 dark:bg-indigo-900/50">
                                        <User className="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    </div>
                                ) : (
                                    <div className="w-8 h-8 flex items-end justify-center flex-shrink-0 drop-shadow-sm">
                                        <LoginBear className="w-8 h-8" />
                                    </div>
                                )}
                                <div className="flex flex-col gap-1 max-w-[80%]">
                                    <div className={`rounded-2xl p-3 text-sm shadow-sm overflow-hidden ${
                                        isUser
                                            ? 'bg-indigo-600 text-white rounded-tr-sm'
                                            : msg.isError
                                                ? 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800/50 rounded-tl-sm'
                                                : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-tl-sm'
                                    }`}>
                                        {isUser ? (
                                            <p className="whitespace-pre-wrap">{displayText}</p>
                                        ) : (
                                            <div className="prose prose-sm dark:prose-invert max-w-none prose-p:leading-relaxed prose-pre:bg-gray-100 dark:prose-pre:bg-gray-900 prose-pre:border prose-pre:border-gray-200 dark:prose-pre:border-gray-800 prose-table:text-xs break-words whitespace-pre-wrap">
                                                <ReactMarkdown>{displayText}</ReactMarkdown>
                                            </div>
                                        )}
                                    </div>
                                    
                                    {/* Action Buttons below message */}
                                    <div className={`flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity ${isUser ? 'justify-end' : 'justify-start'} px-1`}>
                                        {isUser && index === lastUserIndex && !isLoading && (
                                            <button 
                                                onClick={() => handleEditPrompt(index)}
                                                className="flex items-center gap-1 text-[10px] text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                            >
                                                <Pencil className="w-3 h-3" /> Edit
                                            </button>
                                        )}
                                        {!isUser && !msg.isError && (
                                            <button 
                                                onClick={() => handleCopy(displayText, index)}
                                                className="flex items-center gap-1 text-[10px] text-gray-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                                            >
                                                {copiedIndex === index ? <Check className="w-3 h-3 text-emerald-500" /> : <Copy className="w-3 h-3" />}
                                                {copiedIndex === index ? 'Tersalin' : 'Salin'}
                                            </button>
                                        )}
                                    </div>
                                    
                                    {/* Contextual Suggested Prompts */}
                                    {!isUser && !msg.isError && index === messages.length - 1 && suggestions.length > 0 && !isLoading && (
                                        <div className="mt-1 flex flex-wrap gap-2 px-1">
                                            {suggestions.map((suggestion, i) => (
                                                <button
                                                    key={i}
                                                    onClick={() => handleChipClick(suggestion)}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-medium bg-white dark:bg-gray-800 border border-emerald-100 dark:border-emerald-900/50 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 hover:border-emerald-300 dark:hover:bg-emerald-900/40 dark:hover:border-emerald-700 transition-all shadow-sm"
                                                >
                                                    <Sparkles className="w-3 h-3" />
                                                    {suggestion}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}

                    {/* Suggested Prompt Chips — only when fresh */}
                    {showChips && !isLoading && (
                        <div className="space-y-2">
                            <p className="text-[11px] text-gray-400 dark:text-gray-500 font-medium flex items-center gap-1">
                                <Sparkles className="w-3 h-3" />
                                Pertanyaan cepat:
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {SUGGESTED_PROMPTS.map((chip) => (
                                    <button
                                        key={chip.label}
                                        onClick={() => handleChipClick(chip.prompt)}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 dark:hover:bg-emerald-950/40 dark:hover:border-emerald-700 dark:hover:text-emerald-400 transition-all shadow-sm"
                                    >
                                        <span>{chip.icon}</span>
                                        <span>{chip.label}</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

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
