import useVuelidate, {GlobalConfig, Validation, ValidationArgs} from "@vuelidate/core";
import {useResettableRef} from "~/functions/useResettableRef";
import {computed, ComputedRef, MaybeRef, unref} from "vue";
import {useEventBus} from "@vueuse/core";
import {cloneDeep, merge} from "lodash";
import {Ref} from "vue-demi";
import {ApiGenericForm} from "~/entities/ApiInterfaces.ts";

type Form = ApiGenericForm

type ValidationFunc<T extends Form = Form> = (options: GlobalConfig) => ValidationArgs<T>
type BlankFormFunc<T extends Form = Form> = (options: GlobalConfig) => T

export type VuelidateValidations<T extends Form = Form> = ValidationArgs<T> | ValidationFunc<T>
export type VuelidateBlankForm<T extends Form = Form> = MaybeRef<T> | BlankFormFunc<T>

export type VuelidateObject<T extends Form = Form> = Validation<ValidationArgs<T>, T>
export type VuelidateRef<T extends Form = Form> = MaybeRef<VuelidateObject<T>>

export function useFormTabEventBus<T extends Form = Form>() {
    return useEventBus<'build', (formPiece: VuelidateBlankForm<Partial<T>>) => void>('form_tabs');
}

export function useVuelidateOnForm<T extends Form = Form>(
    validations: VuelidateValidations<T> = {} as VuelidateValidations<T>,
    blankForm: VuelidateBlankForm<T> = {} as VuelidateBlankForm<T>,
    options: GlobalConfig = {}
): {
    form: Ref<T>,
    resetForm: () => void,
    v$: VuelidateRef<T>,
    isValid: ComputedRef<boolean>,
    validate: () => Promise<boolean>,
    ifValid: (cb: () => void) => void
} {
    const formEventBus = useFormTabEventBus<T>();

    // Build the blank form from any children elements to the one using this function.
    const buildBlankForm = () => {
        let parsedBlankForm = unref(blankForm);
        if (typeof parsedBlankForm === 'function') {
            parsedBlankForm = parsedBlankForm(options);
        }

        parsedBlankForm = cloneDeep(parsedBlankForm);

        formEventBus.emit('build', (originalNewForm) => {
            let newForm = unref(originalNewForm);
            if (typeof newForm === 'function') {
                newForm = newForm(options);
            }

            merge(parsedBlankForm, newForm);
        });

        return parsedBlankForm;
    }

    const {record: form, reset} = useResettableRef(buildBlankForm);

    const parsedValidations = (typeof validations === 'function')
        ? validations(options)
        : validations;

    const v$ = useVuelidate(parsedValidations, form, options);

    const resetForm = () => {
        reset();
        v$.value.$reset();
    }

    const isValid = computed(() => {
        return !v$.value.$invalid;
    });

    const validate = () => {
        v$.value.$touch();

        return v$.value.$validate();
    }

    const ifValid = (cb: () => void) => {
        void validate().then((isValid) => {
            if (!isValid) {
                return;
            }

            cb();
        });
    }

    return {
        form,
        resetForm,
        v$,
        isValid,
        validate,
        ifValid
    };
}
