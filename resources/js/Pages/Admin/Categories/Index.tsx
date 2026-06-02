import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, PageProps } from '@/types';
import { DndContext, DragEndEvent, KeyboardSensor, PointerSensor, closestCenter, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, arrayMove, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, HTMLAttributes, useEffect, useState } from 'react';
import { useTranslations } from '@/lib/translations';

function CategoryRow({ category, dragHandleProps }: { category: Category; dragHandleProps?: HTMLAttributes<HTMLButtonElement> }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [editing, setEditing] = useState(false);
    const { data, setData, patch, processing, errors } = useForm({
        name: category.name,
        description: category.description ?? '',
        is_active: Boolean(category.is_active),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.categories.update', category.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    const destroy = () => {
        if (window.confirm(t('common.delete_category_confirm', `Delete category "${category.name}"?`).replace('{name}', category.name))) {
            router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true });
        }
    };

    if (editing) {
        return (
            <form onSubmit={submit} className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <div className="grid gap-4 lg:grid-cols-[220px_1fr_auto]">
                    <div>
                        <input value={data.name} onChange={(event) => setData('name', event.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                    </div>
                    <div>
                        <input value={data.description} onChange={(event) => setData('description', event.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                    </div>
                    <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" checked={data.is_active} onChange={(event) => setData('is_active', event.target.checked)} />
                        {data.is_active ? t('common.active', 'Active') : t('common.inactive', 'Inactive')}
                    </label>
                </div>
                <div className="mt-4 flex gap-2">
                    <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('actions.save', 'Save')}</button>
                    <button type="button" onClick={() => setEditing(false)} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{t('actions.cancel', 'Cancel')}</button>
                </div>
            </form>
        );
    }

    return (
        <article className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        {dragHandleProps && (
                            <button
                                type="button"
                                {...dragHandleProps}
                                className="cursor-grab rounded-md border border-gray-300 px-2 py-1 text-sm font-bold text-gray-500 hover:bg-gray-50 active:cursor-grabbing dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
                                aria-label={t('admin.categories.drag_handle', 'Drag to reorder')}
                            >
                                ⋮⋮
                            </button>
                        )}
                        <h2 className="text-lg font-bold text-gray-950 dark:text-white">{category.name}</h2>
                        <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200">{category.slug}</span>
                        <span className={category.is_active ? 'rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700' : 'rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500'}>
                            {category.is_active ? t('common.active', 'Active') : t('common.inactive', 'Inactive')}
                        </span>
                    </div>
                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{category.description || t('profile.no_description', 'No description.')}</p>
                    <p className="mt-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{category.mods_count ?? 0} {t('common.mods_count_label', 'mods')}</p>
                </div>
                <div className="flex gap-2">
                    <button onClick={() => setEditing(true)} className="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">{t('actions.edit', 'Edit')}</button>
                    <button onClick={destroy} className="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">{t('actions.delete', 'Delete')}</button>
                </div>
            </div>
        </article>
    );
}

function SortableCategoryRow({ category }: { category: Category }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: category.id });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div ref={setNodeRef} style={style} className={isDragging ? 'opacity-60' : undefined}>
            <CategoryRow category={category} dragHandleProps={{ ...attributes, ...listeners }} />
        </div>
    );
}

export default function Index({ categories, flash }: PageProps<{ categories: Category[] }>) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        description: '',
        is_active: true,
    });
    const [orderedCategories, setOrderedCategories] = useState(categories);
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    useEffect(() => {
        setOrderedCategories(categories);
    }, [categories]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('admin.categories.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const reorderCategories = (items: Category[]) => {
        const reordered = items.map((category, index) => ({
            ...category,
            sort_order: (index + 1) * 10,
        }));

        setOrderedCategories(reordered);
        router.patch(
            route('admin.categories.reorder'),
            {
                categories: reordered.map((category) => ({ id: category.id, sort_order: category.sort_order })),
            },
            { preserveScroll: true },
        );
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const oldIndex = orderedCategories.findIndex((category) => category.id === active.id);
        const newIndex = orderedCategories.findIndex((category) => category.id === over.id);

        if (oldIndex === -1 || newIndex === -1) {
            return;
        }

        reorderCategories(arrayMove(orderedCategories, oldIndex, newIndex));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.categories.title', 'Categories')}</h2>}>
            <Head title={t('admin.categories.title', 'Categories')} />

            <div className="py-12">
                <div className="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
                    <form onSubmit={submit} className="h-fit rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h1 className="text-xl font-bold text-gray-950 dark:text-white">{t('admin.categories.new_category', 'New category')}</h1>
                        {flash.status && <div className="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{flash.status}</div>}
                        {flash.error && <div className="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800">{flash.error}</div>}

                        <div className="mt-5 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.categories.name', 'Name')}</label>
                                <input value={data.name} onChange={(event) => setData('name', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.categories.description', 'Description (optional)')}</label>
                                <textarea value={data.description} onChange={(event) => setData('description', event.target.value)} rows={4} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                            </div>
                            <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" checked={data.is_active} onChange={(event) => setData('is_active', event.target.checked)} />
                                {t('common.active', 'Active')}
                            </label>
                            <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('common.create', 'Create')}</button>
                        </div>
                    </form>

                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-bold text-gray-950 dark:text-white">{t('admin.categories.order_heading', 'Category order')}</h2>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.categories.order_hint', 'Drag categories to change their display order.')}</p>
                        </div>
                        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                            <SortableContext items={orderedCategories.map((category) => category.id)} strategy={verticalListSortingStrategy}>
                                <div className="space-y-4">
                                    {orderedCategories.map((category) => <SortableCategoryRow key={category.id} category={category} />)}
                                </div>
                            </SortableContext>
                        </DndContext>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
