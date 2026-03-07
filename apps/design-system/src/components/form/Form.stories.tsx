import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import { AtomyQInput } from './AtomyQInput';
import { AtomyQSelect } from './AtomyQSelect';
import { AtomyQCheckbox } from './AtomyQCheckbox';
import { AtomyQSwitch } from './AtomyQSwitch';
import { AtomyQRadioGroup } from './AtomyQRadioGroup';
import { AtomyQTextarea } from './AtomyQTextarea';
import { AtomyQButton } from '../basic/AtomyQButton';
import { Search, Mail, DollarSign, Calendar } from 'lucide-react';

const meta: Meta = {
  title: 'Components/Form/All Form Controls',
  parameters: { layout: 'padded' },
};

export default meta;

export const TextInput: StoryObj = {
  render: () => (
    <div className="space-y-4 max-w-sm">
      <AtomyQInput label="RFQ Title" placeholder="Enter title..." required />
      <AtomyQInput label="Search" placeholder="Search vendors..." leftIcon={<Search />} />
      <AtomyQInput label="Email" type="email" placeholder="name@company.com" leftIcon={<Mail />} />
      <AtomyQInput label="Budget" placeholder="0.00" leftIcon={<DollarSign />} type="number" hint="Enter amount in MYR" />
      <AtomyQInput label="Deadline" type="date" leftIcon={<Calendar />} />
      <AtomyQInput label="With Error" placeholder="..." error="This field is required" />
      <AtomyQInput label="Disabled" placeholder="Cannot edit" disabled />
    </div>
  ),
};

export const SelectDropdown: StoryObj = {
  render: function Render() {
    const [value, setValue] = useState('');
    return (
      <div className="space-y-4 max-w-sm">
        <AtomyQSelect
          label="Category"
          placeholder="Select category..."
          options={[
            { value: 'equipment', label: 'Equipment' },
            { value: 'it-services', label: 'IT Services' },
            { value: 'office', label: 'Office Supplies' },
            { value: 'manufacturing', label: 'Manufacturing' },
            { value: 'logistics', label: 'Logistics' },
          ]}
          value={value}
          onValueChange={setValue}
          required
        />
        <AtomyQSelect
          label="Priority"
          placeholder="Select priority..."
          options={[
            { value: 'low', label: 'Low' },
            { value: 'medium', label: 'Medium' },
            { value: 'high', label: 'High' },
            { value: 'critical', label: 'Critical' },
          ]}
          value=""
          hint="Higher priority RFQs get faster processing"
        />
        <AtomyQSelect
          label="With Error"
          placeholder="Select..."
          options={[{ value: 'a', label: 'Option A' }]}
          value=""
          error="Please select an option"
        />
      </div>
    );
  },
};

export const Checkboxes: StoryObj = {
  render: function Render() {
    const [checked, setChecked] = useState(false);
    return (
      <div className="space-y-3 max-w-sm">
        <AtomyQCheckbox label="Accept terms and conditions" checked={checked} onCheckedChange={() => setChecked(!checked)} />
        <AtomyQCheckbox label="Auto-send vendor invitations" description="Automatically email vendors when RFQ is published" />
        <AtomyQCheckbox label="Include warranty terms" description="Request warranty information from vendors" />
        <AtomyQCheckbox label="Indeterminate state" indeterminate description="Some items selected" />
        <AtomyQCheckbox label="Disabled option" disabled />
      </div>
    );
  },
};

export const Switches: StoryObj = {
  render: function Render() {
    const [auto, setAuto] = useState(true);
    const [notify, setNotify] = useState(false);
    return (
      <div className="space-y-4 max-w-sm">
        <AtomyQSwitch label="Auto-save drafts" description="Automatically save changes every 30 seconds" checked={auto} onCheckedChange={setAuto} />
        <AtomyQSwitch label="Email notifications" description="Receive email alerts for quote submissions" checked={notify} onCheckedChange={setNotify} />
        <AtomyQSwitch label="Dark mode" description="Currently not supported" disabled />
      </div>
    );
  },
};

export const RadioButtons: StoryObj = {
  render: function Render() {
    const [value, setValue] = useState('manual');
    return (
      <div className="max-w-sm">
        <AtomyQRadioGroup
          label="Save Preference"
          options={[
            { value: 'auto', label: 'Auto-save', description: 'Save changes automatically as you type' },
            { value: 'manual', label: 'Manual save', description: 'Only save when you click the Save button' },
            { value: 'interval', label: 'Save on interval', description: 'Auto-save every 5 minutes' },
          ]}
          value={value}
          onValueChange={setValue}
        />
      </div>
    );
  },
};

export const TextareaField: StoryObj = {
  render: function Render() {
    const [value, setValue] = useState('');
    return (
      <div className="space-y-4 max-w-md">
        <AtomyQTextarea label="Description" placeholder="Enter RFQ description..." hint="Provide details about what you need" />
        <AtomyQTextarea
          label="Notes"
          placeholder="Add notes..."
          value={value}
          onChange={(e) => setValue(e.target.value)}
          maxLength={500}
          showCount
        />
        <AtomyQTextarea label="Rejection Reason" placeholder="..." error="Reason is required when rejecting" required />
      </div>
    );
  },
};

export const CompleteForm: StoryObj = {
  name: 'Complete Form Example (Create RFQ)',
  render: () => (
    <div className="max-w-2xl bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
      <div className="px-6 py-4 border-b border-[var(--aq-border-default)]">
        <h2 className="text-base font-semibold text-[var(--aq-text-primary)]">Create New RFQ</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mt-0.5">Fill in the details to create a new Request for Quotation.</p>
      </div>
      <div className="px-6 py-4 space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <AtomyQInput label="Title" placeholder="Enter RFQ title..." required />
          <AtomyQSelect
            label="Category"
            placeholder="Select..."
            options={[
              { value: 'equipment', label: 'Equipment' },
              { value: 'it', label: 'IT Services' },
              { value: 'office', label: 'Office' },
            ]}
            required
          />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <AtomyQInput label="Budget (MYR)" placeholder="0.00" type="number" leftIcon={<DollarSign />} required />
          <AtomyQInput label="Deadline" type="date" leftIcon={<Calendar />} required />
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
        />
        <AtomyQTextarea label="Description" placeholder="Describe your requirements..." />
        <AtomyQCheckbox label="Auto-invite pre-qualified vendors" description="Automatically send invitations to vendors matching the category" />
      </div>
      <div className="px-6 py-4 border-t border-[var(--aq-border-default)] flex justify-end gap-2">
        <AtomyQButton variant="outline">Save as Draft</AtomyQButton>
        <AtomyQButton variant="primary">Create & Publish</AtomyQButton>
      </div>
    </div>
  ),
};
