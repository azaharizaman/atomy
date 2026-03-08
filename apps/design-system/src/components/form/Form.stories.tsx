import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn, expect, within, userEvent } from 'storybook/test';
import { useState } from 'react';
import { AtomyQInput } from './AtomyQInput';
import { AtomyQSelect } from './AtomyQSelect';
import { AtomyQCheckbox } from './AtomyQCheckbox';
import { AtomyQSwitch } from './AtomyQSwitch';
import { AtomyQRadioGroup } from './AtomyQRadioGroup';
import { AtomyQTextarea } from './AtomyQTextarea';
import { AtomyQButton } from '../basic/AtomyQButton';
import { Search, Mail, DollarSign, Calendar, User, Phone } from 'lucide-react';

const meta: Meta = {
  title: 'Components/Form/All Form Controls',
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Comprehensive form controls for building data entry interfaces in AtomyQ. Includes inputs, selects, checkboxes, switches, radio groups, and textareas with full accessibility support.',
      },
    },
  },
};

export default meta;

// ==================== INPUT STORIES ====================

export const TextInput: StoryObj<typeof AtomyQInput> = {
  render: (args) => (
    <div className="space-y-4 max-w-sm">
      <AtomyQInput {...args} />
    </div>
  ),
  args: {
    label: 'RFQ Title',
    placeholder: 'Enter title...',
    required: false,
    disabled: false,
    onChange: fn(),
    onFocus: fn(),
    onBlur: fn(),
  },
  argTypes: {
    label: { control: 'text', description: 'Label text for the input' },
    placeholder: { control: 'text', description: 'Placeholder text' },
    hint: { control: 'text', description: 'Hint text below the input' },
    error: { control: 'text', description: 'Error message to display' },
    required: { control: 'boolean', description: 'Mark field as required' },
    disabled: { control: 'boolean', description: 'Disable the input' },
    type: { 
      control: 'select', 
      options: ['text', 'email', 'number', 'password', 'date', 'tel', 'url'],
      description: 'Input type',
    },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const input = canvas.getByRole('textbox');
    
    await expect(input).toBeInTheDocument();
    await userEvent.type(input, 'Test input value');
    await expect(args.onChange).toHaveBeenCalled();
    await expect(input).toHaveValue('Test input value');
  },
};

export const InputWithIcon: StoryObj<typeof AtomyQInput> = {
  render: () => (
    <div className="space-y-4 max-w-sm">
      <AtomyQInput label="Search" placeholder="Search vendors..." leftIcon={<Search />} />
      <AtomyQInput label="Email" type="email" placeholder="name@company.com" leftIcon={<Mail />} />
      <AtomyQInput label="Budget" placeholder="0.00" leftIcon={<DollarSign />} type="number" hint="Enter amount in MYR" />
      <AtomyQInput label="Deadline" type="date" leftIcon={<Calendar />} />
      <AtomyQInput label="Contact" placeholder="John Doe" leftIcon={<User />} />
      <AtomyQInput label="Phone" placeholder="+60 12 345 6789" leftIcon={<Phone />} type="tel" />
    </div>
  ),
};

export const InputStates: StoryObj<typeof AtomyQInput> = {
  name: 'Input States',
  render: () => (
    <div className="space-y-4 max-w-sm">
      <AtomyQInput label="Default" placeholder="Default state..." />
      <AtomyQInput label="With Hint" placeholder="Enter value..." hint="This is helpful hint text" />
      <AtomyQInput label="With Error" placeholder="..." error="This field is required" />
      <AtomyQInput label="Disabled" placeholder="Cannot edit" disabled value="Read-only value" />
      <AtomyQInput label="Required Field" placeholder="Required..." required />
    </div>
  ),
};

// ==================== SELECT STORIES ====================

export const SelectDropdown: StoryObj<typeof AtomyQSelect> = {
  render: function Render(args) {
    const [value, setValue] = useState('');
    return (
      <div className="space-y-4 max-w-sm">
        <AtomyQSelect
          {...args}
          value={value}
          onValueChange={(val) => {
            setValue(val);
            args.onValueChange?.(val);
          }}
        />
      </div>
    );
  },
  args: {
    label: 'Category',
    placeholder: 'Select category...',
    options: [
      { value: 'equipment', label: 'Equipment' },
      { value: 'it-services', label: 'IT Services' },
      { value: 'office', label: 'Office Supplies' },
      { value: 'manufacturing', label: 'Manufacturing' },
      { value: 'logistics', label: 'Logistics' },
    ],
    required: false,
    disabled: false,
    onValueChange: fn(),
  },
  argTypes: {
    label: { control: 'text', description: 'Label text for the select' },
    placeholder: { control: 'text', description: 'Placeholder text when nothing is selected' },
    hint: { control: 'text', description: 'Hint text below the select' },
    error: { control: 'text', description: 'Error message to display' },
    required: { control: 'boolean', description: 'Mark field as required' },
    disabled: { control: 'boolean', description: 'Disable the select' },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const trigger = canvas.getByRole('combobox');
    
    await expect(trigger).toBeInTheDocument();
    await userEvent.click(trigger);
  },
};

export const SelectVariants: StoryObj = {
  render: function Render() {
    const [priority, setPriority] = useState('medium');
    return (
      <div className="space-y-4 max-w-sm">
        <AtomyQSelect
          label="Priority"
          placeholder="Select priority..."
          options={[
            { value: 'low', label: 'Low' },
            { value: 'medium', label: 'Medium' },
            { value: 'high', label: 'High' },
            { value: 'critical', label: 'Critical' },
          ]}
          value={priority}
          onValueChange={setPriority}
          hint="Higher priority RFQs get faster processing"
        />
        <AtomyQSelect
          label="With Error"
          placeholder="Select..."
          options={[{ value: 'a', label: 'Option A' }]}
          value=""
          error="Please select an option"
        />
        <AtomyQSelect
          label="Disabled Select"
          placeholder="Cannot change..."
          options={[{ value: 'locked', label: 'Locked Option' }]}
          value="locked"
          disabled
        />
      </div>
    );
  },
};

// ==================== CHECKBOX STORIES ====================

export const Checkboxes: StoryObj<typeof AtomyQCheckbox> = {
  render: function Render(args) {
    const [checked, setChecked] = useState(false);
    return (
      <div className="space-y-3 max-w-sm">
        <AtomyQCheckbox 
          {...args}
          checked={checked} 
          onCheckedChange={(val) => {
            setChecked(val as boolean);
            args.onCheckedChange?.(val);
          }} 
        />
      </div>
    );
  },
  args: {
    label: 'Accept terms and conditions',
    description: 'I agree to the terms of service and privacy policy',
    checked: false,
    disabled: false,
    indeterminate: false,
    onCheckedChange: fn(),
  },
  argTypes: {
    label: { control: 'text', description: 'Label text for the checkbox' },
    description: { control: 'text', description: 'Description text below label' },
    checked: { control: 'boolean', description: 'Checked state' },
    disabled: { control: 'boolean', description: 'Disable the checkbox' },
    indeterminate: { control: 'boolean', description: 'Show indeterminate state' },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const checkbox = canvas.getByRole('checkbox');
    
    await expect(checkbox).toBeInTheDocument();
    await userEvent.click(checkbox);
    await expect(args.onCheckedChange).toHaveBeenCalledWith(true);
  },
};

export const CheckboxVariants: StoryObj = {
  render: function Render() {
    const [accepted, setAccepted] = useState(false);
    const [autoInvite, setAutoInvite] = useState(true);
    return (
      <div className="space-y-3 max-w-sm">
        <AtomyQCheckbox 
          label="Accept terms and conditions" 
          checked={accepted} 
          onCheckedChange={() => setAccepted(!accepted)} 
        />
        <AtomyQCheckbox 
          label="Auto-send vendor invitations" 
          description="Automatically email vendors when RFQ is published" 
          checked={autoInvite}
          onCheckedChange={() => setAutoInvite(!autoInvite)}
        />
        <AtomyQCheckbox 
          label="Include warranty terms" 
          description="Request warranty information from vendors" 
        />
        <AtomyQCheckbox 
          label="Indeterminate state" 
          indeterminate 
          description="Some items selected" 
        />
        <AtomyQCheckbox 
          label="Disabled option" 
          disabled 
        />
      </div>
    );
  },
};

// ==================== SWITCH STORIES ====================

export const Switches: StoryObj<typeof AtomyQSwitch> = {
  render: function Render(args) {
    const [checked, setChecked] = useState(true);
    return (
      <div className="space-y-4 max-w-sm">
        <AtomyQSwitch 
          {...args}
          checked={checked} 
          onCheckedChange={(val) => {
            setChecked(val);
            args.onCheckedChange?.(val);
          }} 
        />
      </div>
    );
  },
  args: {
    label: 'Auto-save drafts',
    description: 'Automatically save changes every 30 seconds',
    checked: true,
    disabled: false,
    onCheckedChange: fn(),
  },
  argTypes: {
    label: { control: 'text', description: 'Label text for the switch' },
    description: { control: 'text', description: 'Description text below label' },
    checked: { control: 'boolean', description: 'Checked state' },
    disabled: { control: 'boolean', description: 'Disable the switch' },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const switchEl = canvas.getByRole('switch');
    
    await expect(switchEl).toBeInTheDocument();
    await userEvent.click(switchEl);
    await expect(args.onCheckedChange).toHaveBeenCalled();
  },
};

export const SwitchVariants: StoryObj = {
  render: function Render() {
    const [auto, setAuto] = useState(true);
    const [notify, setNotify] = useState(false);
    const [ai, setAi] = useState(true);
    return (
      <div className="space-y-4 max-w-sm">
        <AtomyQSwitch 
          label="Auto-save drafts" 
          description="Automatically save changes every 30 seconds" 
          checked={auto} 
          onCheckedChange={setAuto} 
        />
        <AtomyQSwitch 
          label="Email notifications" 
          description="Receive email alerts for quote submissions" 
          checked={notify} 
          onCheckedChange={setNotify} 
        />
        <AtomyQSwitch 
          label="AI Assistance" 
          description="Enable AI-powered suggestions and comparisons" 
          checked={ai} 
          onCheckedChange={setAi} 
        />
        <AtomyQSwitch 
          label="Dark mode" 
          description="Currently not supported" 
          disabled 
        />
      </div>
    );
  },
};

// ==================== RADIO GROUP STORIES ====================

export const RadioButtons: StoryObj<typeof AtomyQRadioGroup> = {
  render: function Render(args) {
    const [value, setValue] = useState('manual');
    return (
      <div className="max-w-sm">
        <AtomyQRadioGroup
          {...args}
          value={value}
          onValueChange={(val) => {
            setValue(val);
            args.onValueChange?.(val);
          }}
        />
      </div>
    );
  },
  args: {
    label: 'Save Preference',
    options: [
      { value: 'auto', label: 'Auto-save', description: 'Save changes automatically as you type' },
      { value: 'manual', label: 'Manual save', description: 'Only save when you click the Save button' },
      { value: 'interval', label: 'Save on interval', description: 'Auto-save every 5 minutes' },
    ],
    onValueChange: fn(),
  },
  argTypes: {
    label: { control: 'text', description: 'Label text for the radio group' },
    error: { control: 'text', description: 'Error message to display' },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const radios = canvas.getAllByRole('radio');
    
    await expect(radios).toHaveLength(3);
    await userEvent.click(radios[0]);
    await expect(args.onValueChange).toHaveBeenCalledWith('auto');
  },
};

export const RadioGroupVariants: StoryObj = {
  render: function Render() {
    const [comparison, setComparison] = useState('ai');
    return (
      <div className="space-y-6 max-w-sm">
        <AtomyQRadioGroup
          label="Comparison Method"
          options={[
            { value: 'ai', label: 'AI-Assisted', description: 'Use AI to analyze and compare quotes automatically' },
            { value: 'manual', label: 'Manual Review', description: 'Review and compare quotes manually' },
            { value: 'hybrid', label: 'Hybrid', description: 'AI suggestions with manual final approval' },
          ]}
          value={comparison}
          onValueChange={setComparison}
        />
        <AtomyQRadioGroup
          label="With Error"
          options={[
            { value: 'a', label: 'Option A' },
            { value: 'b', label: 'Option B' },
          ]}
          error="Please select an option"
        />
      </div>
    );
  },
};

// ==================== TEXTAREA STORIES ====================

export const TextareaField: StoryObj<typeof AtomyQTextarea> = {
  render: function Render(args) {
    const [value, setValue] = useState('');
    return (
      <div className="space-y-4 max-w-md">
        <AtomyQTextarea
          {...args}
          value={value}
          onChange={(e) => {
            setValue(e.target.value);
            args.onChange?.(e);
          }}
        />
      </div>
    );
  },
  args: {
    label: 'Description',
    placeholder: 'Enter RFQ description...',
    hint: 'Provide details about what you need',
    maxLength: 500,
    showCount: true,
    required: false,
    disabled: false,
    onChange: fn(),
  },
  argTypes: {
    label: { control: 'text', description: 'Label text for the textarea' },
    placeholder: { control: 'text', description: 'Placeholder text' },
    hint: { control: 'text', description: 'Hint text below the textarea' },
    error: { control: 'text', description: 'Error message to display' },
    maxLength: { control: 'number', description: 'Maximum character length' },
    showCount: { control: 'boolean', description: 'Show character count' },
    required: { control: 'boolean', description: 'Mark field as required' },
    disabled: { control: 'boolean', description: 'Disable the textarea' },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const textarea = canvas.getByRole('textbox');
    
    await expect(textarea).toBeInTheDocument();
    await userEvent.type(textarea, 'Test description text');
    await expect(args.onChange).toHaveBeenCalled();
  },
};

export const TextareaVariants: StoryObj = {
  render: function Render() {
    const [notes, setNotes] = useState('');
    return (
      <div className="space-y-4 max-w-md">
        <AtomyQTextarea 
          label="Description" 
          placeholder="Enter RFQ description..." 
          hint="Provide details about what you need" 
        />
        <AtomyQTextarea
          label="Notes"
          placeholder="Add notes..."
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          maxLength={500}
          showCount
        />
        <AtomyQTextarea 
          label="Rejection Reason" 
          placeholder="..." 
          error="Reason is required when rejecting" 
          required 
        />
        <AtomyQTextarea 
          label="Disabled Textarea" 
          placeholder="..." 
          disabled 
          value="This content cannot be edited" 
        />
      </div>
    );
  },
};

// ==================== COMPLETE FORM EXAMPLE ====================

export const CompleteForm: StoryObj = {
  name: 'Complete Form Example (Create RFQ)',
  render: function Render() {
    const [formData, setFormData] = useState({
      title: '',
      category: '',
      budget: '',
      deadline: '',
      priority: '',
      description: '',
      autoInvite: true,
    });

    const handleSubmit = fn();

    return (
      <div className="max-w-2xl bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
        <div className="px-6 py-4 border-b border-[var(--aq-border-default)]">
          <h2 className="text-base font-semibold text-[var(--aq-text-primary)]">Create New RFQ</h2>
          <p className="text-sm text-[var(--aq-text-muted)] mt-0.5">Fill in the details to create a new Request for Quotation.</p>
        </div>
        <div className="px-6 py-4 space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <AtomyQInput 
              label="Title" 
              placeholder="Enter RFQ title..." 
              required 
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
            />
            <AtomyQSelect
              label="Category"
              placeholder="Select..."
              options={[
                { value: 'equipment', label: 'Equipment' },
                { value: 'it', label: 'IT Services' },
                { value: 'office', label: 'Office Supplies' },
              ]}
              required
              value={formData.category}
              onValueChange={(val) => setFormData({ ...formData, category: val })}
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <AtomyQInput 
              label="Budget (MYR)" 
              placeholder="0.00" 
              type="number" 
              leftIcon={<DollarSign />} 
              required 
              value={formData.budget}
              onChange={(e) => setFormData({ ...formData, budget: e.target.value })}
            />
            <AtomyQInput 
              label="Deadline" 
              type="date" 
              leftIcon={<Calendar />} 
              required 
              value={formData.deadline}
              onChange={(e) => setFormData({ ...formData, deadline: e.target.value })}
            />
          </div>
          <AtomyQSelect
            label="Priority"
            placeholder="Select priority..."
            options={[
              { value: 'low', label: 'Low' },
              { value: 'medium', label: 'Medium' },
              { value: 'high', label: 'High' },
              { value: 'critical', label: 'Critical' },
            ]}
            value={formData.priority}
            onValueChange={(val) => setFormData({ ...formData, priority: val })}
          />
          <AtomyQTextarea 
            label="Description" 
            placeholder="Describe your requirements..." 
            value={formData.description}
            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
          />
          <AtomyQCheckbox 
            label="Auto-invite pre-qualified vendors" 
            description="Automatically send invitations to vendors matching the category" 
            checked={formData.autoInvite}
            onCheckedChange={(val) => setFormData({ ...formData, autoInvite: val as boolean })}
          />
        </div>
        <div className="px-6 py-4 border-t border-[var(--aq-border-default)] flex justify-end gap-2">
          <AtomyQButton variant="outline">Save as Draft</AtomyQButton>
          <AtomyQButton variant="primary" onClick={handleSubmit}>Create & Publish</AtomyQButton>
        </div>
      </div>
    );
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    
    const titleInput = canvas.getByLabelText(/Title/i);
    await userEvent.type(titleInput, 'Industrial Pumping Equipment');
    await expect(titleInput).toHaveValue('Industrial Pumping Equipment');
    
    const budgetInput = canvas.getByLabelText(/Budget/i);
    await userEvent.type(budgetInput, '450000');
    await expect(budgetInput).toHaveValue(450000);
  },
};

export const FormValidation: StoryObj = {
  name: 'Form with Validation States',
  render: () => (
    <div className="max-w-md space-y-4 p-6 bg-white rounded-lg border border-[var(--aq-border-default)]">
      <AtomyQInput 
        label="Email" 
        type="email" 
        placeholder="name@company.com" 
        leftIcon={<Mail />}
        error="Please enter a valid email address"
      />
      <AtomyQSelect
        label="Department"
        placeholder="Select department..."
        options={[
          { value: 'procurement', label: 'Procurement' },
          { value: 'finance', label: 'Finance' },
        ]}
        error="Department selection is required"
      />
      <AtomyQTextarea 
        label="Comments" 
        placeholder="Enter comments..." 
        error="Comments must be at least 10 characters"
      />
      <AtomyQRadioGroup
        label="Approval Type"
        options={[
          { value: 'auto', label: 'Automatic' },
          { value: 'manual', label: 'Manual' },
        ]}
        error="Please select an approval type"
      />
    </div>
  ),
};
