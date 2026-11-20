declare module '*.jsx' {
    import type { ComponentType, ReactNode } from 'react';

    const component: ComponentType<{ reportContent?: ReactNode }>;

    export default component;
}
