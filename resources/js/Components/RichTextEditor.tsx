import { Color } from '@tiptap/extension-color';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { useEffect, useRef, useState } from 'react';

type RichTextEditorProps = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    placeholders?: Record<string, string>;
    onInsertPlaceholder?: (placeholder: string) => void;
    error?: string;
};

export default function RichTextEditor({ value, onChange, placeholder = 'Start typing...', placeholders = {}, onInsertPlaceholder, error }: RichTextEditorProps) {
    const [isSourceMode, setIsSourceMode] = useState(false);
    const [sourceHtml, setSourceHtml] = useState(value);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3],
                },
            }),
            Underline,
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    class: 'text-indigo-600 underline',
                },
            }),
            Placeholder.configure({ placeholder }),
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Color,
        ],
        content: value,
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
        editorProps: {
            attributes: {
                class: 'prose prose-sm max-w-none focus:outline-none dark:prose-invert min-h-[200px] p-3',
            },
        },
    });

    useEffect(() => {
        if (editor && !isSourceMode) {
            const currentHtml = editor.getHTML();
            if (currentHtml !== value) {
                editor.commands.setContent(value);
            }
        }
    }, [value, editor, isSourceMode]);

    const toggleSourceMode = () => {
        if (isSourceMode) {
            if (editor) {
                editor.commands.setContent(sourceHtml);
                onChange(sourceHtml);
            }
        } else {
            if (editor) {
                setSourceHtml(editor.getHTML());
            }
        }
        setIsSourceMode(!isSourceMode);
    };

    const handleSourceChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        setSourceHtml(e.target.value);
        onChange(e.target.value);
    };

    const handleInsertPlaceholder = (placeholder: string) => {
        if (isSourceMode && textareaRef.current) {
            const textarea = textareaRef.current;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const newValue = sourceHtml.substring(0, start) + placeholder + sourceHtml.substring(end);
            setSourceHtml(newValue);
            onChange(newValue);
            setTimeout(() => {
                textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
                textarea.focus();
            }, 0);
        } else if (editor) {
            editor.commands.insertContent(placeholder);
        }
    };

    if (!editor) {
        return null;
    }

    const ToolbarButton = ({ active, onClick, title, children }: { active?: boolean; onClick: () => void; title: string; children: React.ReactNode }) => (
        <button
            type="button"
            onClick={onClick}
            title={title}
            className={`rounded px-2 py-1 text-sm font-medium transition ${
                active
                    ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
            }`}
        >
            {children}
        </button>
    );

    const Separator = () => <span className="mx-1 border-l border-gray-300 dark:border-gray-600" />;

    return (
        <div className="rounded-md border border-gray-300 dark:border-gray-600">
            <div className="flex flex-wrap items-center gap-1 border-b border-gray-300 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-800">
                <ToolbarButton active={editor.isActive('bold')} onClick={() => editor.chain().focus().toggleBold().run()} title="Bold">
                    <strong>B</strong>
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('italic')} onClick={() => editor.chain().focus().toggleItalic().run()} title="Italic">
                    <em>I</em>
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('underline')} onClick={() => editor.chain().focus().toggleUnderline().run()} title="Underline">
                    <span className="underline">U</span>
                </ToolbarButton>

                <Separator />

                <ToolbarButton active={editor.isActive('heading', { level: 1 })} onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()} title="Heading 1">
                    H1
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('heading', { level: 2 })} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} title="Heading 2">
                    H2
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('heading', { level: 3 })} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} title="Heading 3">
                    H3
                </ToolbarButton>

                <Separator />

                <ToolbarButton active={editor.isActive('bulletList')} onClick={() => editor.chain().focus().toggleBulletList().run()} title="Bullet List">
                    • List
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('orderedList')} onClick={() => editor.chain().focus().toggleOrderedList().run()} title="Ordered List">
                    1. List
                </ToolbarButton>

                <Separator />

                <ToolbarButton active={editor.isActive('link')} onClick={() => {
                    const url = window.prompt('Enter URL:');
                    if (url) {
                        editor.chain().focus().setLink({ href: url }).run();
                    }
                }} title="Insert Link">
                    🔗
                </ToolbarButton>

                <Separator />

                <ToolbarButton active={editor.isActive({ textAlign: 'left' })} onClick={() => editor.chain().focus().setTextAlign('left').run()} title="Align Left">
                    ←
                </ToolbarButton>
                <ToolbarButton active={editor.isActive({ textAlign: 'center' })} onClick={() => editor.chain().focus().setTextAlign('center').run()} title="Align Center">
                    ↔
                </ToolbarButton>
                <ToolbarButton active={editor.isActive({ textAlign: 'right' })} onClick={() => editor.chain().focus().setTextAlign('right').run()} title="Align Right">
                    →
                </ToolbarButton>

                <Separator />

                <ToolbarButton active={editor.isActive('blockquote')} onClick={() => editor.chain().focus().toggleBlockquote().run()} title="Blockquote">
                    ❝
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('codeBlock')} onClick={() => editor.chain().focus().toggleCodeBlock().run()} title="Code Block">
                    {'</>'}
                </ToolbarButton>
                <ToolbarButton onClick={() => editor.chain().focus().setHorizontalRule().run()} title="Horizontal Rule">
                    ―
                </ToolbarButton>

                <Separator />

                <ToolbarButton active={isSourceMode} onClick={toggleSourceMode} title="Toggle HTML Source">
                    {'</>'} HTML
                </ToolbarButton>

                {Object.keys(placeholders).length > 0 && (
                    <>
                        <Separator />
                        <div className="relative">
                            <details className="group">
                                <summary className="cursor-pointer rounded px-2 py-1 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 list-none">
                                    {'{...}'} Placeholder
                                </summary>
                                <div className="absolute right-0 z-10 mt-1 w-64 rounded-md border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                    <p className="mb-1 text-xs font-semibold text-gray-500 dark:text-gray-400">Click to insert</p>
                                    <div className="flex flex-wrap gap-1">
                                        {Object.entries(placeholders).map(([key, desc]) => (
                                            <button
                                                key={key}
                                                type="button"
                                                onClick={() => handleInsertPlaceholder(key)}
                                                className="rounded bg-indigo-50 px-2 py-1 text-xs font-mono text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900 dark:text-indigo-200 dark:hover:bg-indigo-800"
                                                title={desc}
                                            >
                                                {key}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </details>
                        </div>
                    </>
                )}
            </div>

            {isSourceMode ? (
                <textarea
                    ref={textareaRef}
                    value={sourceHtml}
                    onChange={handleSourceChange}
                    className="min-h-[200px] w-full rounded-b-md border-0 p-3 font-mono text-sm focus:outline-none dark:bg-gray-900 dark:text-gray-100"
                    spellCheck={false}
                />
            ) : (
                <div className="rounded-b-md bg-white dark:bg-gray-900">
                    <EditorContent editor={editor} />
                </div>
            )}

            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}
