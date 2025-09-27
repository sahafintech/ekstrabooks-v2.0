import React, { useState, useRef, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SidebarInset } from '@/Components/ui/sidebar';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Toaster } from '@/Components/ui/toaster';
import PageHeader from '@/Components/PageHeader';
import { MessageCircle, Send, Loader2, Bot, User } from 'lucide-react';

export default function Chat({ auth }) {
    const { props } = usePage();
    const [messages, setMessages] = useState([
        {
            id: 1,
            type: 'bot',
            content: 'Hello! I\'m your eKstraBooks AI assistant. I can help you analyze your financial data, answer questions about customers, invoices, expenses, and more. What would you like to know?',
            timestamp: new Date()
        }
    ]);
    const [inputMessage, setInputMessage] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!inputMessage.trim() || isLoading) return;

        const userMessage = {
            id: Date.now(),
            type: 'user',
            content: inputMessage,
            timestamp: new Date()
        };

        setMessages(prev => [...prev, userMessage]);
        setInputMessage('');
        setIsLoading(true);

        try {
            // Get CSRF token from meta tag or props
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || props.csrf_token;
            
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }
            
            const response = await fetch('/user/ai/chat/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: inputMessage })
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response error:', response.status, errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            const botMessage = {
                id: Date.now() + 1,
                type: 'bot',
                content: data.response,
                isError: data.type === 'error',
                timestamp: new Date()
            };

            setMessages(prev => [...prev, botMessage]);
        } catch (error) {
            console.error('Chat error:', error);
            const errorMessage = {
                id: Date.now() + 1,
                type: 'bot',
                content: 'Sorry, I encountered an error. Please try again.',
                isError: true,
                timestamp: new Date()
            };
            setMessages(prev => [...prev, errorMessage]);
        } finally {
            setIsLoading(false);
            inputRef.current?.focus();
        }
    };

    const formatTime = (timestamp) => {
        return new Intl.DateTimeFormat('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        }).format(timestamp);
    };

    const exampleQuestions = [
        "What are my top 5 customers by revenue?",
        "Show me overdue invoices",
        "What's my total revenue this month?",
        "Which products sell the most?",
        "How much do customers owe me?"
    ];

    const handleExampleClick = (question) => {
        setInputMessage(question);
        inputRef.current?.focus();
    };

    return (
        <AuthenticatedLayout>
            <Head title="AI Chat Assistant" />
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="AI Assistant"
                        subpage="Chat"
                        url="ai.chat"
                    />
                    <div className="p-4">
                        {/* Status Badge */}
                        <div className="flex items-center justify-between mb-6">
                            <div className="flex items-center space-x-3">
                                <Bot className="h-8 w-8 text-blue-600" />
                                <div>
                                    <h1 className="text-2xl font-bold text-gray-900">AI Chat Assistant</h1>
                                    <p className="text-gray-600">Your intelligent financial data companion</p>
                                </div>
                            </div>
                            <Badge variant="outline" className="flex items-center space-x-2">
                                <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span>Online</span>
                            </Badge>
                        </div>

                        {/* Chat Container */}
                        <div className="bg-white border rounded-lg">
                            {/* Chat Messages */}
                            <div className="h-96 overflow-y-auto p-4 space-y-4 bg-gray-50/50">
                                {messages.map((message) => (
                                    <div
                                        key={message.id}
                                        className={`flex ${message.type === 'user' ? 'justify-end' : 'justify-start'}`}
                                    >
                                        <div className="flex items-start space-x-2 max-w-2xl">
                                            {message.type === 'bot' && (
                                                <div className="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <Bot className="w-4 h-4 text-blue-600" />
                                                </div>
                                            )}
                                            <div
                                                className={`px-4 py-3 rounded-lg ${
                                                    message.type === 'user'
                                                        ? 'bg-blue-600 text-white'
                                                        : message.isError
                                                        ? 'bg-red-50 text-red-800 border border-red-200'
                                                        : 'bg-white text-gray-800 border border-gray-200'
                                                }`}
                                            >
                                                {message.isError && message.content.includes('🚫') ? (
                                                    <div className="flex items-center">
                                                        <Badge variant="destructive" className="mr-2">
                                                            Error
                                                        </Badge>
                                                        <span>{message.content.replace('🚫 ', '')}</span>
                                                    </div>
                                                ) : (
                                                    <div className="whitespace-pre-wrap">{message.content}</div>
                                                )}
                                                <div
                                                    className={`text-xs mt-2 ${
                                                        message.type === 'user' ? 'text-blue-100' : 'text-gray-500'
                                                    }`}
                                                >
                                                    {formatTime(message.timestamp)}
                                                </div>
                                            </div>
                                            {message.type === 'user' && (
                                                <div className="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <User className="w-4 h-4 text-gray-600" />
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                
                                {isLoading && (
                                    <div className="flex justify-start">
                                        <div className="flex items-start space-x-2 max-w-2xl">
                                            <div className="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <Bot className="w-4 h-4 text-blue-600" />
                                            </div>
                                            <div className="bg-white text-gray-800 border border-gray-200 px-4 py-3 rounded-lg">
                                                <div className="flex items-center space-x-2">
                                                    <Loader2 className="w-4 h-4 animate-spin" />
                                                    <span className="text-sm text-gray-500">AI is thinking...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                                <div ref={messagesEndRef} />
                            </div>

                            {/* Example Questions */}
                            {messages.length === 1 && (
                                <div className="px-4 py-4 border-t border-gray-200 bg-white">
                                    <h4 className="text-sm font-medium text-gray-700 mb-3">Try asking:</h4>
                                    <div className="flex flex-wrap gap-2">
                                        {exampleQuestions.map((question, index) => (
                                            <Button
                                                key={index}
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleExampleClick(question)}
                                                className="text-xs"
                                            >
                                                {question}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Chat Input */}
                            <div className="border-t border-gray-200 p-4 bg-white">
                                <form onSubmit={handleSubmit} className="flex space-x-3">
                                    <Input
                                        ref={inputRef}
                                        type="text"
                                        value={inputMessage}
                                        onChange={(e) => setInputMessage(e.target.value)}
                                        placeholder="Ask me about your financial data..."
                                        className="flex-1"
                                        disabled={isLoading}
                                    />
                                    <Button
                                        type="submit"
                                        disabled={!inputMessage.trim() || isLoading}
                                        className="flex items-center space-x-2"
                                    >
                                        {isLoading ? (
                                            <>
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                                <span>Sending...</span>
                                            </>
                                        ) : (
                                            <>
                                                <Send className="w-4 h-4" />
                                                <span>Send</span>
                                            </>
                                        )}
                                    </Button>
                                </form>
                                <p className="text-xs text-gray-500 mt-2">
                                    💡 I can help with customer analysis, invoice tracking, expense reports, and more financial insights.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}