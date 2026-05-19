import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RichTextEditor from '@/Components/RichTextEditor';
import { Faq, PageProps } from '@/types';
import { DndContext, closestCenter, DragEndEvent, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { useTranslations } from '@/lib/translations';

function SortableFaqRow({ faq }: { faq: Faq }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: faq.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        zIndex: isDragging ? 10 : 1,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div ref={setNodeRef} style={style} className={isDragging ? 'rounded-2xl bg-indigo-50 dark:bg-indigo-950' : ''}>
            <FaqRow faq={faq} dragHandleProps={{ ...attributes, ...listeners }} />
        </div>
    );
}

function FaqRow({ faq, dragHandleProps }: { faq: Faq; dragHandleProps: Record<string, any> }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [editing, setEditing] = useState(false);
    const [isOpen, setIsOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm({
        question_en: faq.question_en,
        question_de: faq.question_de,
        answer_en: faq.answer_en,
        answer_de: faq.answer_de,
        is_active: Boolean(faq.is_active),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.faqs.update', faq.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    const destroy = () => {
        if (window.confirm(t('common.delete_faq_confirm', 'Delete this FAQ?'))) {
            router.delete(route('admin.faqs.destroy', faq.id), { preserveScroll: true });
        }
    };

    if (editing) {
        return (
            <form onSubmit={submit} className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-lg font-bold text-gray-950 dark:text-white">{t('actions.edit', 'Edit')}</h3>
                    <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" checked={data.is_active} onChange={(event) => setData('is_active', event.target.checked)} />
                        {data.is_active ? t('common.active', 'Active') : t('common.inactive', 'Inactive')}
                    </label>
                </div>
                <div className="grid gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.question_en', 'Question (English)')}</label>
                        <input value={data.question_en} onChange={(event) => setData('question_en', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.question_en && <p className="mt-1 text-sm text-red-600">{errors.question_en}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.question_de', 'Question (German)')}</label>
                        <input value={data.question_de} onChange={(event) => setData('question_de', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.question_de && <p className="mt-1 text-sm text-red-600">{errors.question_de}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.answer_en', 'Answer (English)')}</label>
                        <RichTextEditor
                            value={data.answer_en}
                            onChange={(value) => setData('answer_en', value)}
                            error={errors.answer_en}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.answer_de', 'Answer (German)')}</label>
                        <RichTextEditor
                            value={data.answer_de}
                            onChange={(value) => setData('answer_de', value)}
                            error={errors.answer_de}
                        />
                    </div>
                </div>
                <div className="mt-4 flex gap-2">
                    <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('actions.save', 'Save')}</button>
                    <button type="button" onClick={() => setEditing(false)} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{t('actions.cancel', 'Cancel')}</button>
                </div>
            </form>
        );
    }

    return (
        <article className="rounded-2xl bg-white shadow-sm dark:bg-gray-800">
            <div className="flex items-start gap-3 p-5">
                <button
                    {...dragHandleProps}
                    className="mt-1 cursor-grab rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 active:cursor-grabbing dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    title="Drag to reorder"
                >
                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M4 8h16M4 16h16" />
                    </svg>
                </button>
                <button
                    onClick={() => setIsOpen(!isOpen)}
                    className="flex flex-1 items-center justify-between text-left"
                >
                    <div className="flex items-center gap-3">
                        <h2 className="text-lg font-bold text-gray-950 dark:text-white">{faq.question_en}</h2>
                        <span className={faq.is_active ? 'rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700' : 'rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500'}>
                            {faq.is_active ? t('common.active', 'Active') : t('common.inactive', 'Inactive')}
                        </span>
                    </div>
                    <svg
                        className={`h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth="2"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div className="flex gap-2">
                    <button onClick={(e) => { e.stopPropagation(); setEditing(true); }} className="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">{t('actions.edit', 'Edit')}</button>
                    <button onClick={(e) => { e.stopPropagation(); destroy(); }} className="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">{t('actions.delete', 'Delete')}</button>
                </div>
            </div>
            <div
                className={`overflow-hidden transition-all duration-300 ${
                    isOpen ? 'max-h-screen opacity-100' : 'max-h-0 opacity-0'
                }`}
            >
                <div className="border-t border-gray-100 px-5 pb-5 pt-3 dark:border-gray-700">
                    <div
                        className="prose prose-sm max-w-none text-gray-600 dark:prose-invert dark:text-gray-300"
                        dangerouslySetInnerHTML={{ __html: faq.answer_en }}
                    />
                </div>
            </div>
        </article>
    );
}

export default function Index({ faqs, flash }: PageProps<{ faqs: Faq[] }>) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [localFaqs, setLocalFaqs] = useState<Faq[]>(faqs);
    const { data, setData, post, processing, errors, reset } = useForm({
        question_en: '',
        question_de: '',
        answer_en: '',
        answer_de: '',
        is_active: true,
    });

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        })
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('admin.faqs.store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                reset();
                const props = page.props as unknown as { faqs: Faq[] };
                setLocalFaqs(props.faqs);
            },
        });
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const oldIndex = localFaqs.findIndex((faq) => faq.id === active.id);
        const newIndex = localFaqs.findIndex((faq) => faq.id === over.id);

        const reordered = [...localFaqs];
        const [movedItem] = reordered.splice(oldIndex, 1);
        reordered.splice(newIndex, 0, movedItem);

        const updated = reordered.map((faq, index) => ({
            ...faq,
            sort_order: index * 10,
        }));

        setLocalFaqs(updated);

        router.patch(
            route('admin.faqs.reorder'),
            { faqs: updated.map((faq) => ({ id: faq.id, sort_order: faq.sort_order })) },
            { preserveScroll: true }
        );
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.faqs.title', 'FAQs')}</h2>}>
            <Head title={t('admin.faqs.title', 'FAQs')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="mb-8 rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div className="flex items-center justify-between">
                            <h1 className="text-xl font-bold text-gray-950 dark:text-white">{t('admin.faqs.new_faq', 'New FAQ')}</h1>
                            <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" checked={data.is_active} onChange={(event) => setData('is_active', event.target.checked)} />
                                {t('common.active', 'Active')}
                            </label>
                        </div>
                        {flash.status && <div className="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{flash.status}</div>}
                        {flash.error && <div className="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800">{flash.error}</div>}

                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.question_en', 'Question (English)')}</label>
                                <input value={data.question_en} onChange={(event) => setData('question_en', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                {errors.question_en && <p className="mt-1 text-sm text-red-600">{errors.question_en}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.question_de', 'Question (German)')}</label>
                                <input value={data.question_de} onChange={(event) => setData('question_de', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                {errors.question_de && <p className="mt-1 text-sm text-red-600">{errors.question_de}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.answer_en', 'Answer (English)')}</label>
                                <RichTextEditor
                                    value={data.answer_en}
                                    onChange={(value) => setData('answer_en', value)}
                                    error={errors.answer_en}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.faqs.answer_de', 'Answer (German)')}</label>
                                <RichTextEditor
                                    value={data.answer_de}
                                    onChange={(value) => setData('answer_de', value)}
                                    error={errors.answer_de}
                                />
                            </div>
                        </div>
                        <button disabled={processing} className="mt-5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('common.create', 'Create')}</button>
                    </form>

                    <section className="space-y-4">
                        {localFaqs.length === 0 && (
                            <p className="rounded-2xl bg-white p-6 text-center text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-300">{t('admin.faqs.no_faqs', 'No FAQs yet.')}</p>
                        )}

                        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                            <SortableContext items={localFaqs.map((faq) => faq.id)} strategy={verticalListSortingStrategy}>
                                {localFaqs.map((faq) => (
                                    <SortableFaqRow key={faq.id} faq={faq} />
                                ))}
                            </SortableContext>
                        </DndContext>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
